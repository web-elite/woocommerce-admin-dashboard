<?php
class Custom_Admin_Dashboard {

    public function __construct() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'dashboard_template'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_process_excel_upload', array($this, 'process_excel_upload'));
        add_action('wp_ajax_get_orders_stats', array($this, 'get_orders_stats'));
        add_action('wp_ajax_export_orders_excel', array($this, 'export_orders_excel'));
        add_action('wp_ajax_get_manage_orders', array($this, 'get_manage_orders'));
        add_action('wp_ajax_update_order_status', array($this, 'update_order_status'));
        add_action('wp_ajax_get_order_details', array($this, 'get_order_details'));
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^admin-dashboard/?$', 'index.php?admin_dashboard=1', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'admin_dashboard';
        return $vars;
    }

    public function dashboard_template() {
        if (get_query_var('admin_dashboard')) {
            if (!is_user_logged_in()) {
                // Show login modal instead of redirect
                include plugin_dir_path(__FILE__) . '../templates/login-required.php';
                exit;
            }

            // Check if user is special member
            $current_user = wp_get_current_user();
            $allowed_users = get_option('allowed_users', array());
            if (!in_array($current_user->ID, $allowed_users)) {
                wp_die('دسترسی ممنوع.');
            }

            // Log dashboard access
            Custom_Admin_Logger::log_access($current_user->ID);

            include plugin_dir_path(__FILE__) . '../templates/dashboard.php';
            exit;
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('dashboard-style', plugin_dir_url(__FILE__) . '../assets/css/dashboard.css');
        wp_enqueue_script('dashboard-script', plugin_dir_url(__FILE__) . '../assets/js/dashboard.js', array('jquery'), '1.0', true);
        wp_localize_script('dashboard-script', 'custom_dashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('process_excel_upload')
        ));
    }

    public function process_excel_upload() {
        // Check nonce for security
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('غیرمجاز');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('دسترسی ممنوع.');
            return;
        }

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $files = $_FILES['excel_file'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($files, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $result = Excel_Processor::process_upload($movefile['file']);
            Custom_Admin_Logger::log_upload($current_user->ID, $files['name'], $movefile['file'], $result);
            wp_send_json_success($result);
        } else {
            Custom_Admin_Logger::log_upload($current_user->ID, $files['name'], '', $movefile['error']);
            wp_send_json_error($movefile['error']);
        }
    }

    public function get_orders_stats() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        $period = sanitize_text_field($_POST['period'] ?? '30');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        // تعیین محدوده زمانی
        $date_query = array();
        switch ($period) {
            case 'today':
                $date_query = array(
                    array(
                        'year' => date('Y'),
                        'month' => date('m'),
                        'day' => date('d')
                    )
                );
                break;
            case 'yesterday':
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $date_query = array(
                    array(
                        'year' => date('Y', strtotime($yesterday)),
                        'month' => date('m', strtotime($yesterday)),
                        'day' => date('d', strtotime($yesterday))
                    )
                );
                break;
            case '7':
                $date_query = array(
                    array(
                        'column' => 'post_date',
                        'after' => '7 days ago',
                        'inclusive' => true
                    )
                );
                break;
            case '30':
                $date_query = array(
                    array(
                        'column' => 'post_date',
                        'after' => '30 days ago',
                        'inclusive' => true
                    )
                );
                break;
            case '90':
                $date_query = array(
                    array(
                        'column' => 'post_date',
                        'after' => '90 days ago',
                        'inclusive' => true
                    )
                );
                break;
            case '365':
                $date_query = array(
                    array(
                        'column' => 'post_date',
                        'after' => '365 days ago',
                        'inclusive' => true
                    )
                );
                break;
            case 'custom':
                if ($start_date && $end_date) {
                    $date_query = array(
                        array(
                            'column' => 'post_date',
                            'after' => $start_date,
                            'before' => $end_date,
                            'inclusive' => true
                        )
                    );
                }
                break;
        }

        // آمار کلی
        $stats = array(
            'total_orders' => $this->get_total_orders_count($date_query),
            'completed_orders' => $this->get_completed_orders_count($date_query),
            'total_revenue' => $this->get_total_revenue($date_query),
            'avg_order' => $this->get_average_order_value($date_query)
        );

        // Debug logging
        error_log('Dashboard Stats Debug: ' . print_r($stats, true));

        // داده‌های چارت
        $chart_data = array(
            'monthly' => $this->get_monthly_sales_data($date_query),
            'status' => $this->get_order_status_data($date_query)
        );

        wp_send_json_success(array(
            'stats' => $stats,
            'chart_data' => $chart_data
        ));
    }

    public function export_orders_excel() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        $period = sanitize_text_field($_POST['period']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $status = sanitize_text_field($_POST['status']);

        // تعیین محدوده زمانی
        $date_query = array();
        switch ($period) {
            case '7':
                $date_query = array(
                    array(
                        'column' => 'post_date',
                        'after' => '7 days ago',
                        'inclusive' => true
                    )
                );
                break;
            case '30':
                $date_query = array(
                    array(
                        'column' => 'post_date',
                        'after' => '30 days ago',
                        'inclusive' => true
                    )
                );
                break;
            case '90':
                $date_query = array(
                    array(
                        'column' => 'post_date',
                        'after' => '90 days ago',
                        'inclusive' => true
                    )
                );
                break;
            case '365':
                $date_query = array(
                    array(
                        'column' => 'post_date',
                        'after' => '365 days ago',
                        'inclusive' => true
                    )
                );
                break;
            case 'custom':
                if ($start_date && $end_date) {
                    $date_query = array(
                        array(
                            'column' => 'post_date',
                            'after' => $start_date,
                            'before' => $end_date,
                            'inclusive' => true
                        )
                    );
                }
                break;
        }

        // کوئری سفارشات
        $args = array(
            'limit' => -1,
            'return' => 'objects'
        );

        if (!empty($date_query)) {
            $args['date_query'] = $date_query;
        }

        if ($status !== 'all') {
            $args['status'] = array($status);
        }

        /** @disregard */
        $orders = wc_get_orders($args);

        // لاگ برای دیباگ
        error_log('Export Orders Debug:');
        error_log('Period: ' . $period);
        error_log('Start Date: ' . $start_date);
        error_log('End Date: ' . $end_date);
        error_log('Status: ' . $status);
        error_log('Args: ' . print_r($args, true));
        error_log('Orders Count: ' . count($orders));

        if (empty($orders)) {
            wp_send_json_error('هیچ سفارشی با شرایط انتخاب شده یافت نشد.');
            return;
        }

        // تولید فایل اکسل
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // هدرها
        $sheet->setCellValue('A1', 'شماره سفارش');
        $sheet->setCellValue('B1', 'تاریخ');
        $sheet->setCellValue('C1', 'مشتری');
        $sheet->setCellValue('D1', 'ایمیل');
        $sheet->setCellValue('E1', 'تلفن');
        $sheet->setCellValue('F1', 'آدرس');
        $sheet->setCellValue('G1', 'وضعیت');
        $sheet->setCellValue('H1', 'مجموع');
        $sheet->setCellValue('I1', 'روش پرداخت');
        $sheet->setCellValue('J1', 'محصولات');

        $row = 2;
        foreach ($orders as $order) {
            $products = array();
            foreach ($order->get_items() as $item) {
                $products[] = $item->get_name() . ' (x' . $item->get_quantity() . ')';
            }

            $sheet->setCellValue('A' . $row, $order->get_id());
            $sheet->setCellValue('B' . $row, $order->get_date_created()->format('Y-m-d H:i:s'));
            $sheet->setCellValue('C' . $row, $order->get_formatted_billing_full_name());
            $sheet->setCellValue('D' . $row, $order->get_billing_email());
            $sheet->setCellValue('E' . $row, $order->get_billing_phone());
            $sheet->setCellValue('F' . $row, $order->get_formatted_billing_address());
            $sheet->setCellValue('G' . $row, $this->get_order_status_name($order->get_status()));
            $sheet->setCellValue('H' . $row, $order->get_total());
            $sheet->setCellValue('I' . $row, $order->get_payment_method_title());
            $sheet->setCellValue('J' . $row, implode(', ', $products));

            $row++;
        }

        // ذخیره فایل
        $filename = 'orders_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filepath = wp_upload_dir()['path'] . '/' . $filename;

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);

        $file_url = wp_upload_dir()['url'] . '/' . $filename;

        wp_send_json_success($file_url);
    }

    public function get_manage_orders() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        $status = sanitize_text_field($_POST['status'] ?? 'processing,pending');
        $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
        $page = intval($_POST['page'] ?? 1);
        $per_page = 20;

        // تنظیمات مرتب‌سازی
        $orderby = 'date';
        $order = 'DESC';

        switch ($sort) {
            case 'date_asc':
                $orderby = 'date';
                $order = 'ASC';
                break;
            case 'total_desc':
                $orderby = 'total';
                $order = 'DESC';
                break;
            case 'total_asc':
                $orderby = 'total';
                $order = 'ASC';
                break;
        }

        // تنظیمات کوئری
        $args = array(
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'orderby' => $orderby,
            'order' => $order,
            'return' => 'objects'
        );

        // پردازش وضعیت‌های چندگانه
        if ($status !== 'all') {
            if (strpos($status, ',') !== false) {
                // چندین وضعیت جدا شده با کاما
                $status_array = array_map('trim', explode(',', $status));
                $args['status'] = $status_array;
            } else {
                // یک وضعیت
                $args['status'] = array($status);
            }
        }

        /** @disregard */
        $orders = wc_get_orders($args);
        $orders_data = array();

        foreach ($orders as $order) {
            $orders_data[] = array(
                'id' => $order->get_id(),
                'customer' => $order->get_formatted_billing_full_name(),
                'phone' => $order->get_billing_phone(),
                'address' => $order->get_formatted_billing_address(),
                'total' => number_format($order->get_total(), 0) . ' تومان',
                'status' => $order->get_status(),
                /** */
                'status_name' => $this->get_order_status_name($order->get_status()),
                'date' => $order->get_date_created()->format('Y-m-d H:i'),
                'notes' => $order->get_customer_note(),
                'print_links' => $this->generate_print_links($order->get_id())
            );
        }

        // تعداد کل سفارشات برای pagination
        $total_args = array('limit' => -1, 'return' => 'ids');
        if ($status !== 'all') {
            if (strpos($status, ',') !== false) {
                // چندین وضعیت جدا شده با کاما
                $status_array = array_map('trim', explode(',', $status));
                $total_args['status'] = $status_array;
            } else {
                // یک وضعیت
                $total_args['status'] = array($status);
            }
        }
        /** @disregard */
        $total_orders = count(wc_get_orders($total_args));
        $total_pages = ceil($total_orders / $per_page);

        wp_send_json_success(array(
            'orders' => $orders_data,
            'pagination' => array(
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_orders' => $total_orders
            )
        ));
    }

    public function update_order_status() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        $order_id = intval($_POST['order_id']);
        $new_status = sanitize_text_field($_POST['new_status']);

        // بررسی وضعیت معتبر
        $valid_statuses = array('processing', 'completed', 'on-hold', 'cancelled');
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error('وضعیت نامعتبر.');
            return;
        }

        /** @disregard */
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('سفارش یافت نشد.');
            return;
        }

        // تغییر وضعیت سفارش
        $order->update_status($new_status);

        // لاگ کردن تغییر وضعیت
        if (method_exists('Custom_Admin_Logger', 'log_order_status_change')) {
            Custom_Admin_Logger::log_order_status_change($current_user->ID, $order_id, $new_status);
        }

                wp_send_json_success(array(
            'message' => 'وضعیت سفارش با موفقیت تغییر یافت.',
            'new_status' => $new_status,
            'new_status_name' => $this->get_order_status_name($new_status)
        ));
    }

    public function get_order_details() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        $order_id = intval($_POST['order_id']);

        /** @disregard */
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('سفارش یافت نشد.');
            return;
        }

        // اطلاعات مشتری
        $customer_info = array(
            'name' => $order->get_formatted_billing_full_name(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'billing_address' => $order->get_formatted_billing_address(),
            'shipping_address' => $order->get_formatted_shipping_address()
        );

        // محصولات سفارش
        $items = array();
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $items[] = array(
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'price' => number_format($item->get_total(), 0) . ' تومان',
                'sku' => $product ? $product->get_sku() : '',
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id()
            );
        }

        // اطلاعات پرداخت
        $payment_info = array(
            'method' => $order->get_payment_method_title(),
            'status' => $this->get_order_status_name($order->get_status()),
            'total' => number_format($order->get_total(), 0) . ' تومان',
            'subtotal' => number_format($order->get_subtotal(), 0) . ' تومان',
            'tax' => number_format($order->get_total_tax(), 0) . ' تومان',
            'shipping' => number_format($order->get_shipping_total(), 0) . ' تومان',
            'discount' => number_format($order->get_total_discount(), 0) . ' تومان'
        );

        // تاریخچه سفارش
        $notes = array();
        $order_notes = $order->get_customer_order_notes();
        foreach ($order_notes as $note) {
            $notes[] = array(
                'date' => $note->comment_date,
                'note' => $note->comment_content,
                'type' => 'customer'
            );
        }

        // اطلاعات کلی سفارش
        $order_info = array(
            'id' => $order->get_id(),
            'date_created' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'date_modified' => $order->get_date_modified() ? $order->get_date_modified()->format('Y-m-d H:i:s') : '',
            'customer_note' => $order->get_customer_note(),
            'status' => $order->get_status(),
            'status_name' => $this->get_order_status_name($order->get_status())
        );

        wp_send_json_success(array(
            'order_info' => $order_info,
            'customer_info' => $customer_info,
            'items' => $items,
            'payment_info' => $payment_info,
            'notes' => $notes
        ));
    }

    public function generate_sample_file() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        $result = Excel_Processor::generate_sample_file();

        if ($result['success']) {
            wp_send_json_success(array(
                'file_url' => $result['file_url'],
                'filename' => $result['filename'],
                'message' => 'فایل نمونه با موفقیت ایجاد شد.'
            ));
        } else {
            wp_send_json_error($result['error']);
        }
    }

    private function get_total_orders_count($date_query = array()) {
        global $wpdb;

        $where_clause = "WHERE post_type = 'shop_order' AND post_status IN ('wc-pending', 'wc-processing', 'wc-completed', 'wc-cancelled', 'wc-refunded')";

        if (!empty($date_query)) {
            $where_clause .= " AND post_date >= '" . date('Y-m-d H:i:s', strtotime($date_query[0]['after'] ?? '30 days ago')) . "'";
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}posts {$where_clause}");
    }

    private function get_completed_orders_count($date_query = array()) {
        global $wpdb;

        $where_clause = "WHERE post_type = 'shop_order' AND post_status = 'wc-completed'";

        if (!empty($date_query)) {
            $where_clause .= " AND post_date >= '" . date('Y-m-d H:i:s', strtotime($date_query[0]['after'] ?? '30 days ago')) . "'";
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}posts {$where_clause}");
    }

    private function get_total_revenue($date_query = array()) {
        global $wpdb;

        $where_clause = "WHERE pm.meta_key = '_order_total' AND p.post_type = 'shop_order' AND p.post_status = 'wc-completed'";

        if (!empty($date_query)) {
            $where_clause .= " AND p.post_date >= '" . date('Y-m-d H:i:s', strtotime($date_query[0]['after'] ?? '30 days ago')) . "'";
        }

        $result = $wpdb->get_var("
            SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->prefix}postmeta pm
            JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
            {$where_clause}
        ");
        return number_format($result ?: 0, 0);
    }

    private function get_average_order_value($date_query = array()) {
        $total_orders = $this->get_completed_orders_count($date_query);
        if ($total_orders == 0) return 0;

        global $wpdb;

        $where_clause = "WHERE pm.meta_key = '_order_total' AND p.post_type = 'shop_order' AND p.post_status = 'wc-completed'";

        if (!empty($date_query)) {
            $where_clause .= " AND p.post_date >= '" . date('Y-m-d H:i:s', strtotime($date_query[0]['after'] ?? '30 days ago')) . "'";
        }

        $total_revenue = $wpdb->get_var("
            SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->prefix}postmeta pm
            JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
            {$where_clause}
        ");

        return number_format(($total_revenue ?: 0) / $total_orders, 0);
    }

    private function get_monthly_sales_data($date_query = array()) {
        global $wpdb;

        $where_clause = "WHERE p.post_type = 'shop_order' AND p.post_status = 'wc-completed' AND pm.meta_key = '_order_total'";

        if (!empty($date_query)) {
            $date_filter = $date_query[0]['after'] ?? '12 months ago';
            $where_clause .= " AND p.post_date >= '" . date('Y-m-d H:i:s', strtotime($date_filter)) . "'";
        } else {
            $where_clause .= " AND p.post_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }

        $results = $wpdb->get_results("
            SELECT
                DATE_FORMAT(p.post_date, '%Y-%m') as month,
                SUM(CAST(pm.meta_value AS DECIMAL(10,2))) as total
            FROM {$wpdb->prefix}posts p
            JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
            {$where_clause}
            GROUP BY DATE_FORMAT(p.post_date, '%Y-%m')
            ORDER BY month ASC
        ");

        $labels = array();
        $data = array();

        foreach ($results as $result) {
            $labels[] = $result->month;
            $data[] = (float) $result->total;
        }

        return array('labels' => $labels, 'data' => $data);
    }

    private function get_order_status_data($date_query = array()) {
        global $wpdb;

        $where_clause = "WHERE post_type = 'shop_order' AND post_status IN ('wc-pending', 'wc-processing', 'wc-completed', 'wc-cancelled')";

        if (!empty($date_query)) {
            $where_clause .= " AND post_date >= '" . date('Y-m-d H:i:s', strtotime($date_query[0]['after'] ?? '30 days ago')) . "'";
        }

        $results = $wpdb->get_results("SELECT post_status, COUNT(*) as count FROM {$wpdb->prefix}posts {$where_clause} GROUP BY post_status");

        $labels = array();
        $data = array();

        $status_names = array(
            'wc-pending' => 'در انتظار پرداخت',
            'wc-processing' => 'در حال پردازش',
            'wc-completed' => 'تکمیل شده',
            'wc-cancelled' => 'لغو شده'
        );

        foreach ($results as $result) {
            $labels[] = $status_names[$result->post_status] ?? $result->post_status;
            $data[] = (int) $result->count;
        }

        return array('labels' => $labels, 'data' => $data);
    }

    private function get_recent_orders() {
        $args = array(
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects'
        );

        /** @disregard */
        $orders = wc_get_orders($args);
        $recent_orders = array();

        foreach ($orders as $order) {
            /** @disregard */
            $recent_orders[] = array(
                'id' => $order->get_id(),
                'customer' => $order->get_formatted_billing_full_name(),
                'total' => number_format($order->get_total(), 0) . ' تومان',
                'status' => $this->get_order_status_name($order->get_status()), 
                'date' => $order->get_date_created()->format('Y-m-d H:i')
            );
        }

        return $recent_orders;
    }

    private function get_orders_needing_attention() {
        // Example logic to get orders that need attention
        $args = array(
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
            'status' => array('wc-pending', 'wc-processing')
        );

        /** @disregard */
        $orders = wc_get_orders($args);
        $attention_orders = array();

        foreach ($orders as $order) {
            /** @disregard */
            $attention_orders[] = array(
                'id' => $order->get_id(),
                'customer' => $order->get_formatted_billing_full_name(),
                'total' => number_format($order->get_total(), 0) . ' تومان',
                'status' => $this->get_order_status_name($order->get_status()), 
                'date' => $order->get_date_created()->format('Y-m-d H:i')
            );
        }

        return $attention_orders;
    }

    private function generate_print_links($order_id) {
        $base_url = admin_url('?post=' . $order_id . '&wooi_page=');
        
        return array(
            'thermal' => $base_url . 'wooi_8fb2e31f',
            'label' => $base_url . 'wooi_f5214e53', 
            'invoice' => $base_url . 'wooi_be15d978'
        );
    }

    private function get_order_status_name($status) {
        $status_names = array(
            'pending' => 'در انتظار پرداخت',
            'processing' => 'در حال پردازش',
            'on-hold' => 'در انتظار',
            'completed' => 'تکمیل شده',
            'cancelled' => 'لغو شده',
            'refunded' => 'بازپرداخت شده',
            'failed' => 'ناموفق'
        );

        return $status_names[$status] ?? $status;
    }
}