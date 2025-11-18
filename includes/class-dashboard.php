<?php
class wc_admin_dashboard
{

    public function __construct()
    {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'dashboard_template'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_process_excel_upload', array($this, 'process_excel_upload'));
        add_action('wp_ajax_get_orders_stats', array($this, 'get_orders_stats'));
        add_action('wp_ajax_export_orders_excel', array($this, 'export_orders_excel'));
        add_action('wp_ajax_get_datatable_orders', array($this, 'get_datatable_orders'));
        add_action('wp_ajax_update_order_status', array($this, 'update_order_status'));
        add_action('wp_ajax_get_customer_details', array($this, 'get_customer_details'));
        add_action('wp_ajax_get_datatable_customers', array($this, 'get_datatable_customers'));
        add_action('wp_ajax_get_customers_stats', array($this, 'get_customers_stats'));
        add_action('wp_ajax_get_analytics_data', array($this, 'get_analytics_data'));
        add_action('wp_ajax_export_sales_report', array($this, 'export_sales_report'));
        add_action('wp_ajax_export_customers_report', array($this, 'export_customers_report'));
        add_action('wp_ajax_export_products_report', array($this, 'export_products_report'));
    }

    public function add_rewrite_rules()
    {
        add_rewrite_rule('^admin-dashboard/?$', 'index.php?admin_dashboard=1', 'top');
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'admin_dashboard';
        return $vars;
    }

    public function dashboard_template()
    {
        if (get_query_var('admin_dashboard')) {
            if (!is_user_logged_in()) {
                // Show login modal instead of redirect
                include plugin_dir_path(__FILE__) . '../templates/login-required.php';
                exit;
            }
            // Check if user is special member
            $current_user = wp_get_current_user();
            $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
            if (!in_array($current_user->ID, $allowed_users)) {
                // wp_die('دسترسی ممنوع.');
                wp_redirect(home_url());
                exit;
            }

            // Log dashboard access
            WC_Admin_Logger::log_access($current_user->ID);

            include plugin_dir_path(__FILE__) . '../templates/dashboard.php';
            exit;
        }
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('dashboard-script', plugin_dir_url(__FILE__) . '../assets/js/dashboard.js', array('jquery'), '1.0', true);
        wp_localize_script('dashboard-script', 'custom_dashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('process_excel_upload')
        ));
    }

    public function process_excel_upload()
    {
        // Check nonce for security
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('غیرمجاز');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
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
            WC_Admin_Logger::log_upload($current_user->ID, $files['name'], $movefile['file'], $result);
            wp_send_json_success($result);
        } else {
            WC_Admin_Logger::log_upload($current_user->ID, $files['name'], '', $movefile['error']);
            wp_send_json_error($movefile['error']);
        }
    }

    public function get_orders_stats()
    {
        error_log('get_orders_stats called');

        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            error_log('User not logged in');
            wp_send_json_error('User not logged in');
            return;
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());

        error_log('Current user ID: ' . $current_user->ID);
        error_log('Current user login: ' . $current_user->user_login);
        error_log('Current user roles: ' . implode(', ', $current_user->roles));
        error_log('Allowed users: ' . print_r($allowed_users, true));

        if (!in_array($current_user->ID, $allowed_users)) {
            error_log('Access denied for user: ' . $current_user->ID);
            wp_send_json_error('Access denied. Please configure allowed users in WooCommerce settings.');
            return;
        }

        error_log('Access granted, processing stats...');

        $period = sanitize_text_field($_POST['period'] ?? '30');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        // Debug logging
        error_log('Dashboard Stats Debug - Period: ' . $period);
        error_log('Dashboard Stats Debug - Start Date: ' . $start_date);
        error_log('Dashboard Stats Debug - End Date: ' . $end_date);

        // آمار کلی
        $stats = array(
            'total_orders' => $this->get_total_orders_count($period),
            'completed_orders' => $this->get_completed_orders_count($period),
            'total_revenue' => $this->get_total_revenue($period),
            'avg_order' => $this->get_average_order_value($period)
        );

        // Debug logging
        error_log('Dashboard Stats Debug - Stats: ' . print_r($stats, true));

        // داده‌های چارت
        $chart_data = array(
            'monthly' => $this->get_monthly_sales_data($period),
            'status' => $this->get_order_status_data($period)
        );

        error_log('Dashboard Stats Debug - Chart Data: ' . print_r($chart_data, true));

        // Check if we have any data
        $has_data = $stats['total_orders'] > 0 || $stats['completed_orders'] > 0 || $stats['total_revenue'] > 0;

        if (!$has_data) {
            error_log('No order data found in database');
            wp_send_json_success(array(
                'stats' => array(
                    'total_orders' => 0,
                    'completed_orders' => 0,
                    'total_revenue' => '0',
                    'avg_order' => '0'
                ),
                'chart_data' => array(
                    'monthly' => array('labels' => array(), 'data' => array()),
                    'status' => array('labels' => array(), 'data' => array())
                ),
                'message' => 'هیچ داده سفارشی در فروشگاه یافت نشد. لطفا ابتدا چند سفارش آزمایشی ایجاد کنید.'
            ));
            return;
        }

        wp_send_json_success(array(
            'stats' => $stats,
            'chart_data' => $chart_data
        ));
    }

    public function export_orders_excel()
    {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
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
            $sheet->setCellValue('B' . $row, $this->gregorian_to_jalali($order->get_date_created(), 'Y/m/d H:i'));
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

        $filename = 'orders_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filepath = wp_upload_dir()['path'] . '/' . $filename;

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);

        $file_url = wp_upload_dir()['url'] . '/' . $filename;
        wp_send_json_success(array('file_url' => $file_url, 'filename' => $filename));
    }

    public function get_datatable_orders()
    {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        // پارامترهای DataTables
        $draw = intval($_POST['draw'] ?? 1);
        $start = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $search = sanitize_text_field($_POST['search']['value'] ?? '');
        $order_column = intval($_POST['order'][0]['column'] ?? 0);
        $order_dir = sanitize_text_field($_POST['order'][0]['dir'] ?? 'desc');

        // فیلترهای اضافی
        $status_filter = sanitize_text_field($_POST['status_filter'] ?? 'all');
        $date_filter = sanitize_text_field($_POST['date_filter'] ?? 'all');
        $single_date = sanitize_text_field($_POST['single_date'] ?? '');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        // ستون‌های قابل مرتب‌سازی
        $columns = array('id', 'customer', 'address', 'notes', 'total', 'status', 'date', 'print', 'actions');
        $orderby = $columns[$order_column] ?? 'date';
        $order = strtoupper($order_dir) === 'ASC' ? 'ASC' : 'DESC';

        // تنظیمات کوئری پایه
        $args = array(
            'limit' => $length,
            'offset' => $start,
            'orderby' => $orderby === 'date' ? 'date' : 'ID',
            'order' => $order,
            'return' => 'objects'
        );

        // اضافه کردن فیلتر وضعیت
        if ($status_filter !== 'all') {
            if (strpos($status_filter, ',') !== false) {
                $status_array = array_map('trim', explode(',', $status_filter));
                $args['status'] = $status_array;
            } else {
                $args['status'] = array($status_filter);
            }
        }

        // اضافه کردن فیلتر تاریخ
        if ($date_filter !== 'all') {
            $date_query = array();

            switch ($date_filter) {
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
                    $yesterday = strtotime('-1 day');
                    $date_query = array(
                        array(
                            'year' => date('Y', $yesterday),
                            'month' => date('m', $yesterday),
                            'day' => date('d', $yesterday)
                        )
                    );
                    break;
                case '7':
                    $date_query = array(
                        array(
                            'after' => '7 days ago'
                        )
                    );
                    break;
                case '30':
                    $date_query = array(
                        array(
                            'after' => '30 days ago'
                        )
                    );
                    break;
                case 'custom':
                    if (!empty($single_date)) {
                        $date_parts = explode('/', $single_date);
                        if (count($date_parts) === 3) {
                            $date_query = array(
                                array(
                                    'year' => $date_parts[0],
                                    'month' => $date_parts[1],
                                    'day' => $date_parts[2]
                                )
                            );
                        }
                    }
                    break;
                case 'range':
                    if (!empty($start_date) && !empty($end_date)) {
                        $start_parts = explode('/', $start_date);
                        $end_parts = explode('/', $end_date);
                        if (count($start_parts) === 3 && count($end_parts) === 3) {
                            $date_query = array(
                                array(
                                    'after' => array(
                                        'year' => $start_parts[0],
                                        'month' => $start_parts[1],
                                        'day' => $start_parts[2]
                                    ),
                                    'before' => array(
                                        'year' => $end_parts[0],
                                        'month' => $end_parts[1],
                                        'day' => $end_parts[2]
                                    ),
                                    'inclusive' => true
                                )
                            );
                        }
                    }
                    break;
            }

            if (!empty($date_query)) {
                $args['date_query'] = $date_query;
            }
        }

        // اضافه کردن جستجو
        if (!empty($search)) {
            $args['s'] = $search;
        }

        /** @disregard */
        $orders = wc_get_orders($args);

        // آماده‌سازی داده‌ها برای DataTables
        $data = array();
        foreach ($orders as $order) {
            $status_name = $this->get_order_status_name($order->get_status());
            $status_class = '';
            switch ($order->get_status()) {
                case 'processing':
                    $status_class = 'bg-yellow-100 text-yellow-800';
                    break;
                case 'completed':
                    $status_class = 'bg-green-100 text-green-800';
                    break;
                case 'on-hold':
                    $status_class = 'bg-orange-100 text-orange-800';
                    break;
                case 'cancelled':
                    $status_class = 'bg-red-100 text-red-800';
                    break;
                default:
                    $status_class = 'bg-gray-100 text-gray-800';
            }

            $print_links = $this->generate_print_links($order->get_id());

            $data[] = array(
                '<a href="javascript:void(0)" class="text-blue-600 hover:text-blue-800 font-medium" onclick="showOrderDetails(' . $order->get_id() . ')">#' . $order->get_id() . ' - ' . $order->get_formatted_billing_full_name() . ' (' . $order->get_billing_phone() . ')</a>',
                '<div class="max-w-xs truncate" title="' . $order->get_formatted_billing_address() . '">' . $order->get_formatted_billing_address() . '</div>',
                '<div class="max-w-xs truncate" title="' . ($order->get_customer_note() ?: '-') . '">' . ($order->get_customer_note() ?: '-') . '</div>',
                '<span class="font-medium text-gray-900">' . number_format($order->get_total(), 0) . ' تومان</span>',
                '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ' . $status_class . '">' . $status_name . '</span>',
                '<span class="text-sm text-gray-500">' . $this->gregorian_to_jalali($order->get_date_created(), 'Y/m/d H:i') . '</span>',
                '<div class="flex justify-center space-x-1">
                    <a href="' . $print_links['thermal'] . '" target="_blank" title="پرینت حرارتی" class="inline-flex items-center p-1 bg-pink-500 text-white text-xs rounded hover:bg-pink-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M7.25 7h9.5V5c0-2-.75-3-3-3h-3.5c-2.25 0-3 1-3 3v2ZM16 15v4c0 2-1 3-3 3h-2c-2 0-3-1-3-3v-4h8Z" stroke="#ffffff" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M21 10v5c0 2-1 3-3 3h-2v-3H8v3H6c-2 0-3-1-3-3v-5c0-2 1-3 3-3h12c2 0 3 1 3 3ZM17 15H7M7 11h3" stroke="#ffffff" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </a>
                    <a href="' . $print_links['label'] . '" target="_blank" title="برچسب" class="inline-flex items-center p-1 bg-teal-500 text-white text-xs rounded hover:bg-teal-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="m4.17 15.3 4.53 4.53a4.78 4.78 0 0 0 6.75 0l4.39-4.39a4.78 4.78 0 0 0 0-6.75L15.3 4.17a4.75 4.75 0 0 0-3.6-1.39l-5 .24c-2 .09-3.59 1.68-3.69 3.67l-.24 5c-.06 1.35.45 2.66 1.4 3.61Z" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9.5 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"></path></svg>
                    </a>
                    <a href="' . $print_links['invoice'] . '" target="_blank" title="فاکتور" class="inline-flex items-center p-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21 7v10c0 3-1.5 5-5 5H8c-3.5 0-5-2-5-5V7c0-3 1.5-5 5-5h8c3.5 0 5 2 5 5Z" stroke="#ffffff" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M15.5 2v7.86c0 .44-.52.66-.84.37l-2.32-2.14a.496.496 0 0 0-.68 0l-2.32 2.14c-.32.29-.84.07-.84-.37V2h7ZM13.25 14h4.25M9 18h8.5" stroke="#ffffff" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </a>
                </div>',
                '<select class="status-select px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" data-order-id="' . $order->get_id() . '">
                    <option value="processing" ' . ($order->get_status() === 'processing' ? 'selected' : '') . '>در حال پردازش</option>
                    <option value="completed" ' . ($order->get_status() === 'completed' ? 'selected' : '') . '>تکمیل شده</option>
                    <option value="on-hold" ' . ($order->get_status() === 'on-hold' ? 'selected' : '') . '>در انتظار</option>
                    <option value="cancelled" ' . ($order->get_status() === 'cancelled' ? 'selected' : '') . '>لغو شده</option>
                </select>
                <button class="view-details-btn ml-2 inline-flex items-center px-2 py-1 bg-gray-600 text-white text-xs rounded hover:bg-gray-700 transition-colors" data-order-id="' . $order->get_id() . '">جزئیات</button>'
            );
        }

        // شمارش کل رکوردها (بدون فیلتر)
        $total_args = array('limit' => -1, 'return' => 'ids');
        if ($status_filter !== 'all') {
            if (strpos($status_filter, ',') !== false) {
                $status_array = array_map('trim', explode(',', $status_filter));
                $total_args['status'] = $status_array;
            } else {
                $total_args['status'] = array($status_filter);
            }
        }

        // اضافه کردن فیلتر تاریخ به total_args
        if ($date_filter !== 'all') {
            $date_query = array();

            switch ($date_filter) {
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
                    $yesterday = strtotime('-1 day');
                    $date_query = array(
                        array(
                            'year' => date('Y', $yesterday),
                            'month' => date('m', $yesterday),
                            'day' => date('d', $yesterday)
                        )
                    );
                    break;
                case '7':
                    $date_query = array(
                        array(
                            'after' => '7 days ago'
                        )
                    );
                    break;
                case '30':
                    $date_query = array(
                        array(
                            'after' => '30 days ago'
                        )
                    );
                    break;
                case 'custom':
                    if (!empty($single_date)) {
                        $date_parts = explode('/', $single_date);
                        if (count($date_parts) === 3) {
                            $date_query = array(
                                array(
                                    'year' => $date_parts[0],
                                    'month' => $date_parts[1],
                                    'day' => $date_parts[2]
                                )
                            );
                        }
                    }
                    break;
                case 'range':
                    if (!empty($start_date) && !empty($end_date)) {
                        $start_parts = explode('/', $start_date);
                        $end_parts = explode('/', $end_date);
                        if (count($start_parts) === 3 && count($end_parts) === 3) {
                            $date_query = array(
                                array(
                                    'after' => array(
                                        'year' => $start_parts[0],
                                        'month' => $start_parts[1],
                                        'day' => $start_parts[2]
                                    ),
                                    'before' => array(
                                        'year' => $end_parts[0],
                                        'month' => $end_parts[1],
                                        'day' => $end_parts[2]
                                    ),
                                    'inclusive' => true
                                )
                            );
                        }
                    }
                    break;
            }

            if (!empty($date_query)) {
                $total_args['date_query'] = $date_query;
            }
        }

        /** @disregard */
        $total_records = count(wc_get_orders($total_args));

        // شمارش رکوردهای فیلتر شده
        $filtered_records = $total_records; // در حال حاضر ساده‌سازی شده

        wp_send_json(array(
            'draw' => $draw,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => $data
        ));
    }

    public function update_order_status()
    {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
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
        if (method_exists('WC_Admin_Logger', 'log_order_status_change')) {
            WC_Admin_Logger::log_order_status_change($current_user->ID, $order_id, $new_status);
        }

        wp_send_json_success(array(
            'message' => 'وضعیت سفارش با موفقیت تغییر یافت.',
            'new_status' => $new_status,
            'new_status_name' => $this->get_order_status_name($new_status)
        ));
    }

    public function get_order_details()
    {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
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
                'date' => $this->gregorian_to_jalali($note->comment_date, 'Y/m/d H:i'),
                'note' => $note->comment_content,
                'type' => 'customer'
            );
        }

        // اطلاعات کلی سفارش
        $order_info = array(
            'id' => $order->get_id(),
            'date_created' => $this->gregorian_to_jalali($order->get_date_created(), 'Y/m/d H:i'),
            'date_modified' => $order->get_date_modified() ? $this->gregorian_to_jalali($order->get_date_modified(), 'Y/m/d H:i') : '',
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

    private function get_total_orders_count($period = '30')
    {
        global $wpdb;

        $date_filter = '';
        switch ($period) {
            case 'today':
                $date_filter = "AND DATE(post_date) = CURDATE()";
                break;
            case 'yesterday':
                $date_filter = "AND DATE(post_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case '7':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '90':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
            case '365':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                break;
        }

        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'shop_order' AND post_status IN ('wc-pending', 'wc-processing', 'wc-completed', 'wc-cancelled', 'wc-refunded') {$date_filter}";
        $result = $wpdb->get_var($query);

        error_log("Total Orders Query: {$query}");
        error_log("Total Orders Result: {$result}");

        return (int) $result;
    }

    private function get_completed_orders_count($period = '30')
    {
        global $wpdb;

        $date_filter = '';
        switch ($period) {
            case 'today':
                $date_filter = "AND DATE(post_date) = CURDATE()";
                break;
            case 'yesterday':
                $date_filter = "AND DATE(post_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case '7':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '90':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
            case '365':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                break;
        }

        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'shop_order' AND post_status = 'wc-completed' {$date_filter}";
        $result = $wpdb->get_var($query);

        error_log("Completed Orders Query: {$query}");
        error_log("Completed Orders Result: {$result}");

        return (int) $result;
    }

    private function get_total_revenue($period = '30')
    {
        global $wpdb;

        $date_filter = '';
        switch ($period) {
            case 'today':
                $date_filter = "AND p.post_date >= CURDATE()";
                break;
            case 'yesterday':
                $date_filter = "AND DATE(p.post_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case '7':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '90':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
            case '365':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                break;
        }

        $query = "
            SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->prefix}postmeta pm
            JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_order_total' AND p.post_type = 'shop_order' AND p.post_status = 'wc-completed' {$date_filter}
        ";
        $result = $wpdb->get_var($query);

        error_log("Total Revenue Query: {$query}");
        error_log("Total Revenue Result: {$result}");

        return number_format($result ?: 0, 0);
    }

    private function get_average_order_value($period = '30')
    {
        $total_orders = $this->get_completed_orders_count($period);
        if ($total_orders == 0) return 0;

        global $wpdb;

        $date_filter = '';
        switch ($period) {
            case 'today':
                $date_filter = "AND p.post_date >= CURDATE()";
                break;
            case 'yesterday':
                $date_filter = "AND DATE(p.post_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case '7':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '90':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
            case '365':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                break;
        }

        $query = "
            SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->prefix}postmeta pm
            JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_order_total' AND p.post_type = 'shop_order' AND p.post_status = 'wc-completed' {$date_filter}
        ";
        $total_revenue = $wpdb->get_var($query);

        error_log("Average Order Query: {$query}");
        error_log("Average Order Total Revenue: {$total_revenue}, Total Orders: {$total_orders}");

        return number_format(($total_revenue ?: 0) / $total_orders, 0);
    }

    private function get_monthly_sales_data($period = '30')
    {
        global $wpdb;

        $date_filter = '';
        switch ($period) {
            case '7':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '90':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
            case '365':
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                break;
            default:
                $date_filter = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }

        $results = $wpdb->get_results("
            SELECT
                DATE_FORMAT(p.post_date, '%Y-%m') as month,
                SUM(CAST(pm.meta_value AS DECIMAL(10,2))) as total
            FROM {$wpdb->prefix}posts p
            JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order' AND p.post_status = 'wc-completed' AND pm.meta_key = '_order_total' {$date_filter}
            GROUP BY DATE_FORMAT(p.post_date, '%Y-%m')
            ORDER BY month ASC
        ");

        $labels = array();
        $data = array();

        foreach ($results as $result) {
            $labels[] = $result->month;
            $data[] = (float) $result->total;
        }

        error_log("Monthly Sales Data: " . print_r(array('labels' => $labels, 'data' => $data), true));

        return array('labels' => $labels, 'data' => $data);
    }

    private function get_order_status_data($period = '30')
    {
        global $wpdb;

        $date_filter = '';
        switch ($period) {
            case 'today':
                $date_filter = "AND DATE(post_date) = CURDATE()";
                break;
            case 'yesterday':
                $date_filter = "AND DATE(post_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case '7':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '90':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
            case '365':
                $date_filter = "AND post_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                break;
        }

        $results = $wpdb->get_results("SELECT post_status, COUNT(*) as count FROM {$wpdb->prefix}posts WHERE post_type = 'shop_order' AND post_status IN ('wc-pending', 'wc-processing', 'wc-completed', 'wc-cancelled') {$date_filter} GROUP BY post_status");

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

        error_log("Order Status Data: " . print_r(array('labels' => $labels, 'data' => $data), true));

        return array('labels' => $labels, 'data' => $data);
    }

    private function get_recent_orders()
    {
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
                'date' => $this->gregorian_to_jalali($order->get_date_created(), 'Y/m/d H:i')
            );
        }

        return $recent_orders;
    }

    private function get_orders_needing_attention()
    {
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
                'date' => $this->gregorian_to_jalali($order->get_date_created(), 'Y/m/d H:i')
            );
        }

        return $attention_orders;
    }

    private function generate_print_links($order_id)
    {
        $base_url = admin_url('?post=' . $order_id . '&wooi_page=');

        return array(
            'thermal' => $base_url . 'wooi_8fb2e31f',
            'label' => $base_url . 'wooi_f5214e53',
            'invoice' => $base_url . 'wooi_be15d978'
        );
    }

    private function get_order_status_name($status)
    {
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

    /**
     * تبدیل تاریخ میلادی به شمسی
     */
    private function gregorian_to_jalali($gregorian_date, $format = 'Y/m/d H:i')
    {
        if (empty($gregorian_date)) {
            return '';
        }

        // اگر ورودی DateTime object است، به string تبدیل کن
        if ($gregorian_date instanceof DateTime) {
            $gregorian_date = $gregorian_date->format('Y-m-d H:i:s');
        }

        // جدا کردن بخش‌های تاریخ
        $date_parts = explode(' ', $gregorian_date);
        $date = $date_parts[0];
        $time = isset($date_parts[1]) ? $date_parts[1] : '';

        $date_parts = explode('-', $date);
        if (count($date_parts) !== 3) {
            return $gregorian_date; // اگر فرمت درست نیست، همان ورودی را برگردان
        }

        $gy = (int) $date_parts[0];
        $gm = (int) $date_parts[1];
        $gd = (int) $date_parts[2];

        $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
        $jy = ($gy <= 1600) ? 0 : 979;
        $gy -= ($gy <= 1600) ? 621 : 1600;
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
        $jy += 33 * ((int)($days / 12053));
        $days %= 12053;
        $jy += 4 * ((int)($days / 1461));
        $days %= 1461;
        $jy += (int)(($days - 1) / 365);
        if ($days > 365) $days = ($days - 1) % 365;
        $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
        $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));

        // فرمت خروجی
        $jalali_date = '';
        if ($format === 'Y/m/d H:i') {
            $jalali_date = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
            if (!empty($time)) {
                $time_parts = explode(':', $time);
                if (count($time_parts) >= 2) {
                    $jalali_date .= ' ' . sprintf('%02d:%02d', $time_parts[0], $time_parts[1]);
                }
            }
        } elseif ($format === 'Y-m-d H:i') {
            $jalali_date = sprintf('%04d-%02d-%02d', $jy, $jm, $jd);
            if (!empty($time)) {
                $time_parts = explode(':', $time);
                if (count($time_parts) >= 2) {
                    $jalali_date .= ' ' . sprintf('%02d:%02d', $time_parts[0], $time_parts[1]);
                }
            }
        } else {
            $jalali_date = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
        }

        return $jalali_date;
    }

    // Analytics methods
    public function get_analytics_data() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        $period = sanitize_text_field($_POST['period'] ?? '30');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        // Get date range
        $date_range = $this->get_date_range($period, $start_date, $end_date);

        $data = array(
            'charts' => array(
                'monthly' => $this->get_monthly_revenue_data($date_range),
                'daily' => $this->get_daily_revenue_data($date_range),
                'distribution' => $this->get_revenue_distribution_data($date_range)
            ),
            'top_products' => $this->get_top_products($date_range),
            'customer_stats' => $this->get_customer_analytics_stats($date_range),
            'province_sales' => $this->get_province_sales_data($date_range),
            'performance' => $this->get_performance_metrics($date_range)
        );

        wp_send_json_success($data);
    }

    public function get_customer_details() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        $customer_id = intval($_POST['customer_id']);

        // Get customer data
        $customer = get_userdata($customer_id);
        if (!$customer) {
            wp_send_json_error('مشتری یافت نشد.');
            return;
        }

        // Get customer orders
        $args = array(
            'customer_id' => $customer_id,
            'limit' => -1,
            'return' => 'objects'
        );

        $orders = wc_get_orders($args);

        $total_orders = count($orders);
        $total_spent = 0;
        $recent_orders = array();

        foreach ($orders as $order) {
            $total_spent += $order->get_total();

            if (count($recent_orders) < 5) {
                $recent_orders[] = array(
                    'id' => $order->get_id(),
                    'date' => $this->gregorian_to_jalali($order->get_date_created(), 'Y/m/d'),
                    'total' => number_format($order->get_total()),
                    'status' => $order->get_status(),
                    'status_name' => $this->get_order_status_name($order->get_status())
                );
            }
        }

        $data = array(
            'name' => $customer->display_name,
            'email' => $customer->user_email,
            'phone' => get_user_meta($customer_id, 'billing_phone', true),
            'registered_date' => $this->gregorian_to_jalali($customer->user_registered, 'Y/m/d'),
            'total_orders' => $total_orders,
            'total_spent' => number_format($total_spent),
            'avg_order_value' => $total_orders > 0 ? number_format($total_spent / $total_orders) : 0,
            'last_order_date' => !empty($orders) ? $this->gregorian_to_jalali($orders[0]->get_date_created(), 'Y/m/d') : 'ندارد',
            'recent_orders' => $recent_orders
        );

        wp_send_json_success($data);
    }

    public function get_datatable_customers() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        // DataTables parameters
        $draw = intval($_POST['draw'] ?? 1);
        $start = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $search = sanitize_text_field($_POST['search'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'name_asc');
        $date_filter = sanitize_text_field($_POST['date_filter'] ?? 'all');

        // Get customers with orders
        $customers = $this->get_customers_with_orders($start, $length, $search, $sort, $date_filter);
        $total_records = $this->get_customers_count($search, $date_filter);

        $data = array();
        foreach ($customers as $customer) {
            $data[] = array(
                $customer['name'],
                $customer['contact_info'],
                $customer['orders_count'] . ' سفارش',
                number_format($customer['total_spent']) . ' تومان',
                $customer['last_order_date'],
                '<button class="view-customer-details-btn bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600" data-customer-id="' . $customer['id'] . '">مشاهده</button>'
            );
        }

        wp_send_json(array(
            'draw' => $draw,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'data' => $data
        ));
    }

    public function get_customers_stats() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        global $wpdb;

        // Total customers
        $total_customers = $wpdb->get_var("
            SELECT COUNT(DISTINCT pm.meta_value)
            FROM {$wpdb->postmeta} pm
            WHERE pm.meta_key = '_customer_user'
            AND pm.meta_value > 0
        ");

        // New customers this month
        $new_customers = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT pm.meta_value)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_customer_user'
            AND pm.meta_value > 0
            AND p.post_date >= %s
        ", date('Y-m-01 00:00:00')));

        // Loyal customers (more than 3 orders)
        $loyal_customers = $wpdb->get_var("
            SELECT COUNT(*)
            FROM (
                SELECT pm.meta_value, COUNT(*) as order_count
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_customer_user'
                AND pm.meta_value > 0
                AND p.post_type = 'shop_order'
                AND p.post_status IN ('wc_completed', 'wc_processing', 'wc_on-hold')
                GROUP BY pm.meta_value
                HAVING order_count > 3
            ) as loyal
        ");

        // Average order value
        $avg_order_value = $wpdb->get_var("
            SELECT AVG(pm.meta_value)
            FROM {$wpdb->postmeta} pm
            WHERE pm.meta_key = '_order_total'
        ");

        wp_send_json_success(array(
            'total' => intval($total_customers),
            'new' => intval($new_customers),
            'loyal' => intval($loyal_customers),
            'avg_order' => number_format(floatval($avg_order_value))
        ));
    }

    // Helper methods for analytics
    private function get_date_range($period, $start_date, $end_date) {
        $now = current_time('timestamp');

        switch ($period) {
            case '7':
                return array(
                    'start' => date('Y-m-d', strtotime('-7 days', $now)),
                    'end' => date('Y-m-d', $now)
                );
            case '30':
                return array(
                    'start' => date('Y-m-d', strtotime('-30 days', $now)),
                    'end' => date('Y-m-d', $now)
                );
            case '90':
                return array(
                    'start' => date('Y-m-d', strtotime('-90 days', $now)),
                    'end' => date('Y-m-d', $now)
                );
            case '365':
                return array(
                    'start' => date('Y-m-d', strtotime('-365 days', $now)),
                    'end' => date('Y-m-d', $now)
                );
            case 'custom':
                return array(
                    'start' => $start_date,
                    'end' => $end_date
                );
            default:
                return array(
                    'start' => date('Y-m-d', strtotime('-30 days', $now)),
                    'end' => date('Y-m-d', $now)
                );
        }
    }

    private function get_monthly_revenue_data($date_range) {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT
                DATE_FORMAT(p.post_date, '%Y-%m') as month,
                SUM(pm.meta_value) as revenue
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc_completed', 'wc_processing', 'wc_on-hold')
            AND pm.meta_key = '_order_total'
            AND DATE(p.post_date) BETWEEN %s AND %s
            GROUP BY DATE_FORMAT(p.post_date, '%Y-%m')
            ORDER BY month ASC
        ", $date_range['start'], $date_range['end']);

        $results = $wpdb->get_results($query);

        $labels = array();
        $data = array();

        foreach ($results as $result) {
            $labels[] = $result->month;
            $data[] = floatval($result->revenue);
        }

        return array('labels' => $labels, 'data' => $data);
    }

    private function get_daily_revenue_data($date_range) {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT
                DATE(p.post_date) as date,
                SUM(pm.meta_value) as revenue
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc_completed', 'wc_processing', 'wc_on-hold')
            AND pm.meta_key = '_order_total'
            AND DATE(p.post_date) BETWEEN %s AND %s
            GROUP BY DATE(p.post_date)
            ORDER BY date ASC
        ", $date_range['start'], $date_range['end']);

        $results = $wpdb->get_results($query);

        $labels = array();
        $data = array();

        foreach ($results as $result) {
            $labels[] = $result->date;
            $data[] = floatval($result->revenue);
        }

        return array('labels' => $labels, 'data' => $data);
    }

    private function get_revenue_distribution_data($date_range) {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT
                CASE
                    WHEN pm.meta_value < 100000 THEN 'کمتر از ۱۰۰ هزار تومان'
                    WHEN pm.meta_value BETWEEN 100000 AND 500000 THEN '۱۰۰ تا ۵۰۰ هزار تومان'
                    WHEN pm.meta_value BETWEEN 500000 AND 1000000 THEN '۵۰۰ هزار تا ۱ میلیون تومان'
                    WHEN pm.meta_value BETWEEN 1000000 AND 5000000 THEN '۱ تا ۵ میلیون تومان'
                    ELSE 'بیشتر از ۵ میلیون تومان'
                END as range,
                COUNT(*) as count
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc_completed', 'wc_processing', 'wc_on-hold')
            AND pm.meta_key = '_order_total'
            AND DATE(p.post_date) BETWEEN %s AND %s
            GROUP BY
                CASE
                    WHEN pm.meta_value < 100000 THEN 'کمتر از ۱۰۰ هزار تومان'
                    WHEN pm.meta_value BETWEEN 100000 AND 500000 THEN '۱۰۰ تا ۵۰۰ هزار تومان'
                    WHEN pm.meta_value BETWEEN 500000 AND 1000000 THEN '۵۰۰ هزار تا ۱ میلیون تومان'
                    WHEN pm.meta_value BETWEEN 1000000 AND 5000000 THEN '۱ تا ۵ میلیون تومان'
                    ELSE 'بیشتر از ۵ میلیون تومان'
                END
        ", $date_range['start'], $date_range['end']);

        $results = $wpdb->get_results($query);

        $labels = array();
        $data = array();

        foreach ($results as $result) {
            $labels[] = $result->range;
            $data[] = intval($result->count);
        }

        return array('labels' => $labels, 'data' => $data);
    }

    private function get_top_products($date_range) {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT
                p.ID,
                p.post_title as name,
                SUM(woim.meta_value) as sales,
                SUM(woim.meta_value * woim2.meta_value) as revenue
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->prefix}woocommerce_order_items woi ON p.ID = woi.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta woim ON woi.order_item_id = woim.order_item_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta woim2 ON woi.order_item_id = woim2.order_item_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc_completed', 'wc_processing', 'wc_on-hold')
            AND woim.meta_key = '_qty'
            AND woim2.meta_key = '_line_total'
            AND DATE(p.post_date) BETWEEN %s AND %s
            GROUP BY p.ID, p.post_title
            ORDER BY revenue DESC
            LIMIT 10
        ", $date_range['start'], $date_range['end']);

        $results = $wpdb->get_results($query);

        $products = array();
        foreach ($results as $result) {
            $products[] = array(
                'name' => $result->name,
                'sales' => intval($result->sales),
                'revenue' => number_format(floatval($result->revenue))
            );
        }

        return $products;
    }

    private function get_customer_analytics_stats($date_range) {
        global $wpdb;

        // Total customers in period
        $total = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT pm.meta_value)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_customer_user'
            AND pm.meta_value > 0
            AND DATE(p.post_date) BETWEEN %s AND %s
        ", $date_range['start'], $date_range['end']));

        // New customers in period
        $new = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            WHERE DATE(u.user_registered) BETWEEN %s AND %s
        ", $date_range['start'], $date_range['end']));

        // Loyal customers (more than 3 orders)
        $loyal = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM (
                SELECT pm.meta_value, COUNT(*) as order_count
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_customer_user'
                AND pm.meta_value > 0
                AND p.post_type = 'shop_order'
                AND p.post_status IN ('wc_completed', 'wc_processing', 'wc_on-hold')
                AND DATE(p.post_date) BETWEEN %s AND %s
                GROUP BY pm.meta_value
                HAVING order_count > 3
            ) as loyal
        ", $date_range['start'], $date_range['end']));

        // Average order value
        $avg_order = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(pm.meta_value)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_order_total'
            AND DATE(p.post_date) BETWEEN %s AND %s
        ", $date_range['start'], $date_range['end']));

        return array(
            'total' => intval($total),
            'new' => intval($new),
            'loyal' => intval($loyal),
            'avg_order' => number_format(floatval($avg_order))
        );
    }

    private function get_province_sales_data($date_range) {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT
                pm2.meta_value as province,
                COUNT(*) as orders,
                SUM(pm.meta_value) as revenue
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE pm.meta_key = '_order_total'
            AND pm2.meta_key = '_billing_state'
            AND p.post_type = 'shop_order'
            AND p.post_status IN ('wc_completed', 'wc_processing', 'wc_on-hold')
            AND DATE(p.post_date) BETWEEN %s AND %s
            AND pm2.meta_value != ''
            GROUP BY pm2.meta_value
            ORDER BY revenue DESC
            LIMIT 10
        ", $date_range['start'], $date_range['end']);

        $results = $wpdb->get_results($query);

        $provinces = array();
        foreach ($results as $result) {
            $provinces[] = array(
                'name' => $result->province,
                'sales' => intval($result->orders),
                'revenue' => number_format(floatval($result->revenue))
            );
        }

        return $provinces;
    }

    private function get_performance_metrics($date_range) {
        global $wpdb;

        // Conversion rate (simplified - orders vs visitors would need more complex tracking)
        $total_orders = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc_completed', 'wc_processing', 'wc_on-hold')
            AND DATE(p.post_date) BETWEEN %s AND %s
        ", $date_range['start'], $date_range['end']));

        // Average processing time (simplified)
        $avg_processing_time = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(TIMESTAMPDIFF(DAY, p.post_date, pm.meta_value))
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status = 'wc_completed'
            AND pm.meta_key = '_completed_date'
            AND DATE(p.post_date) BETWEEN %s AND %s
        ", $date_range['start'], $date_range['end']));

        return array(
            'conversion_rate' => $total_orders > 0 ? min(100, ($total_orders / max(1, $total_orders * 10)) * 100) : 0, // Simplified
            'avg_processing_time' => intval($avg_processing_time),
            'customer_retention' => 75, // Placeholder - would need more complex calculation
            'customer_satisfaction' => 85 // Placeholder - would need review/rating system
        );
    }

    private function get_customers_with_orders($start, $length, $search, $sort, $date_filter) {
        global $wpdb;

        $where_clause = "WHERE pm.meta_key = '_customer_user' AND pm.meta_value > 0";
        $having_clause = "";

        if (!empty($search)) {
            $where_clause .= $wpdb->prepare(" AND (u.display_name LIKE %s OR u.user_email LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
        }

        // Date filter
        if ($date_filter !== 'all') {
            $days = intval($date_filter);
            $date_limit = date('Y-m-d', strtotime("-{$days} days"));
            $where_clause .= $wpdb->prepare(" AND DATE(u.user_registered) >= %s", $date_limit);
        }

        // Sorting
        $order_by = "ORDER BY ";
        switch ($sort) {
            case 'name_desc':
                $order_by .= "u.display_name DESC";
                break;
            case 'orders_desc':
                $order_by .= "order_count DESC";
                break;
            case 'orders_asc':
                $order_by .= "order_count ASC";
                break;
            case 'total_desc':
                $order_by .= "total_spent DESC";
                break;
            case 'total_asc':
                $order_by .= "total_spent ASC";
                break;
            case 'date_desc':
                $order_by .= "u.user_registered DESC";
                break;
            case 'date_asc':
                $order_by .= "u.user_registered ASC";
                break;
            default:
                $order_by .= "u.display_name ASC";
        }

        $query = "
            SELECT
                u.ID as id,
                u.display_name as name,
                u.user_email as email,
                u.user_registered,
                COUNT(o.ID) as order_count,
                COALESCE(SUM(om.meta_value), 0) as total_spent,
                MAX(o.post_date) as last_order_date
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->postmeta} pm ON u.ID = pm.meta_value AND pm.meta_key = '_customer_user'
            LEFT JOIN {$wpdb->posts} o ON pm.post_id = o.ID AND o.post_type = 'shop_order' AND o.post_status IN ('wc_completed', 'wc_processing', 'wc_on-hold')
            LEFT JOIN {$wpdb->postmeta} om ON o.ID = om.post_id AND om.meta_key = '_order_total'
            {$where_clause}
            GROUP BY u.ID, u.display_name, u.user_email, u.user_registered
            {$having_clause}
            {$order_by}
            LIMIT {$start}, {$length}
        ";

        $results = $wpdb->get_results($query);

        $customers = array();
        foreach ($results as $result) {
            $customers[] = array(
                'id' => $result->id,
                'name' => $result->name,
                'contact_info' => $result->email,
                'orders_count' => intval($result->order_count),
                'total_spent' => floatval($result->total_spent),
                'last_order_date' => $result->last_order_date ? $this->gregorian_to_jalali($result->last_order_date, 'Y/m/d') : 'ندارد'
            );
        }

        return $customers;
    }

    private function get_customers_count($search, $date_filter) {
        global $wpdb;

        $where_clause = "WHERE pm.meta_key = '_customer_user' AND pm.meta_value > 0";

        if (!empty($search)) {
            $where_clause .= $wpdb->prepare(" AND (u.display_name LIKE %s OR u.user_email LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
        }

        if ($date_filter !== 'all') {
            $days = intval($date_filter);
            $date_limit = date('Y-m-d', strtotime("-{$days} days"));
            $where_clause .= $wpdb->prepare(" AND DATE(u.user_registered) >= %s", $date_limit);
        }

        $query = "
            SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->postmeta} pm ON u.ID = pm.meta_value AND pm.meta_key = '_customer_user'
            {$where_clause}
        ";

        return intval($wpdb->get_var($query));
    }

    // Export methods
    public function export_sales_report() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        $period = sanitize_text_field($_POST['period'] ?? '30');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        $date_range = $this->get_date_range($period, $start_date, $end_date);

        // Generate Excel file
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'تاریخ');
        $sheet->setCellValue('B1', 'تعداد سفارشات');
        $sheet->setCellValue('C1', 'مجموع فروش');
        $sheet->setCellValue('D1', 'میانگین سفارش');

        // Get daily data
        global $wpdb;
        $query = $wpdb->prepare("
            SELECT
                DATE(p.post_date) as date,
                COUNT(*) as orders,
                SUM(pm.meta_value) as revenue,
                AVG(pm.meta_value) as avg_order
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc_completed', 'wc_processing', 'wc_on-hold')
            AND pm.meta_key = '_order_total'
            AND DATE(p.post_date) BETWEEN %s AND %s
            GROUP BY DATE(p.post_date)
            ORDER BY date ASC
        ", $date_range['start'], $date_range['end']);

        $results = $wpdb->get_results($query);

        $row = 2;
        foreach ($results as $result) {
            $sheet->setCellValue('A' . $row, $this->gregorian_to_jalali($result->date, 'Y/m/d'));
            $sheet->setCellValue('B' . $row, $result->orders);
            $sheet->setCellValue('C' . $row, $result->revenue);
            $sheet->setCellValue('D' . $row, $result->avg_order);
            $row++;
        }

        $filename = 'sales_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filepath = wp_upload_dir()['path'] . '/' . $filename;

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);

        $file_url = wp_upload_dir()['url'] . '/' . $filename;
        wp_send_json_success(array('file_url' => $file_url, 'filename' => $filename));
    }

    public function export_customers_report() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        $period = sanitize_text_field($_POST['period'] ?? '30');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        $date_range = $this->get_date_range($period, $start_date, $end_date);

        // Generate Excel file
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'نام مشتری');
        $sheet->setCellValue('B1', 'ایمیل');
        $sheet->setCellValue('C1', 'تلفن');
        $sheet->setCellValue('D1', 'تعداد سفارشات');
        $sheet->setCellValue('E1', 'مجموع خرید');
        $sheet->setCellValue('F1', 'میانگین سفارش');
        $sheet->setCellValue('G1', 'آخرین سفارش');

        // Get customers data
        $customers = $this->get_customers_with_orders(0, 1000, '', 'name_asc', 'all');

        $row = 2;
        foreach ($customers as $customer) {
            $user = get_userdata($customer['id']);
            $sheet->setCellValue('A' . $row, $customer['name']);
            $sheet->setCellValue('B' . $row, $user->user_email);
            $sheet->setCellValue('C' . $row, get_user_meta($customer['id'], 'billing_phone', true));
            $sheet->setCellValue('D' . $row, $customer['orders_count']);
            $sheet->setCellValue('E' . $row, $customer['total_spent']);
            $sheet->setCellValue('F' . $row, $customer['orders_count'] > 0 ? $customer['total_spent'] / $customer['orders_count'] : 0);
            $sheet->setCellValue('G' . $row, $customer['last_order_date']);
            $row++;
        }

        $filename = 'customers_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filepath = wp_upload_dir()['path'] . '/' . $filename;

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);

        $file_url = wp_upload_dir()['url'] . '/' . $filename;
        wp_send_json_success(array('file_url' => $file_url, 'filename' => $filename));
    }

    public function export_products_report() {
        check_ajax_referer('process_excel_upload', 'nonce');

        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $current_user = wp_get_current_user();
        $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
        if (!in_array($current_user->ID, $allowed_users)) {
            wp_send_json_error('Access denied.');
            return;
        }

        $period = sanitize_text_field($_POST['period'] ?? '30');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        $date_range = $this->get_date_range($period, $start_date, $end_date);

        // Generate Excel file
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'نام محصول');
        $sheet->setCellValue('B1', 'تعداد فروش');
        $sheet->setCellValue('C1', 'مجموع فروش');
        $sheet->setCellValue('D1', 'میانگین قیمت');

        // Get products data
        $products = $this->get_top_products($date_range);

        $row = 2;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product['name']);
            $sheet->setCellValue('B' . $row, $product['sales']);
            $sheet->setCellValue('C' . $row, $product['revenue']);
            $sheet->setCellValue('D' . $row, $product['sales'] > 0 ? $product['revenue'] / $product['sales'] : 0);
            $row++;
        }

        $filename = 'products_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filepath = wp_upload_dir()['path'] . '/' . $filename;

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);

        $file_url = wp_upload_dir()['url'] . '/' . $filename;
        wp_send_json_success(array('file_url' => $file_url, 'filename' => $filename));
    }
}
