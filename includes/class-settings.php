<?php
class Custom_Admin_Settings {

    public function __construct() {
        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
        add_action( 'woocommerce_settings_tabs_wc_admin_dashboard', array( $this, 'settings_tab' ) );
        add_action( 'woocommerce_update_options_wc_admin_dashboard', array( $this, 'update_settings' ) );
        add_action( 'woocommerce_settings_saved', array( $this, 'settings_saved' ) );
    }

    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs['wc_admin_dashboard'] = __( 'داشبورد ادمین', 'woocommerce' );
        return $settings_tabs;
    }

    public function settings_tab() {
        woocommerce_admin_fields( $this->get_settings() );
        // $this->display_logs_section();
    }

    public function update_settings() {
        woocommerce_update_options( $this->get_settings() );
    }

    public function get_settings() {
        $settings = array(
            'section_title' => array(
                'name'     => __( 'تنظیمات داشبورد ادمین ووکامرس', 'woocommerce' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_admin_dashboard_section_title'
            ),
            'allowed_users' => array(
                'name'     => __( 'کاربران مجاز', 'woocommerce' ),
                'type'     => 'multiselect',
                'desc'     => __( 'کاربرانی را که می‌توانند به داشبورد ادمین ووکامرس دسترسی داشته باشند انتخاب کنید.', 'woocommerce' ) . '<br> این لیست شامل مدیران، مدیران فروشگاه و ویراستاران است.',
                'id'       => 'wc_admin_dashboard_allowed_users',
                'options'  => $this->get_user_options(),
                'default'  => array(),
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width: 350px;',
            ),
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_admin_dashboard_section_end'
            )
        );
        return $settings;
    }

    private function get_user_options() {
        $users = get_users( array( 'role__in' => array( 'administrator', 'shop_manager', 'editor' ) ) );
        $options = array();
        foreach ( $users as $user ) {
            $options[ $user->ID ] = $user->display_name . ' (' . $user->user_login . ')';
        }
        return $options;
    }

    public function settings_saved() {
        // Optional: Add any logic after settings are saved
    }

    public function display_logs_section() {
        $log_type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $ip_search = isset($_GET['ip_search']) ? sanitize_text_field($_GET['ip_search']) : '';
        
        $logs = WC_Admin_Logger::get_logs(50, $log_type, $search, $ip_search);
        $total_logs = WC_Admin_Logger::get_total_logs();
        $access_count = WC_Admin_Logger::get_logs_count_by_type('access');
        $upload_count = WC_Admin_Logger::get_logs_count_by_type('upload');
        ?>
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
            <h2>گزارش فعالیت‌ها</h2>
            
            <div class="log-stats" style="background: #fff; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <h3>آمار کلی</h3>
                <div style="display: flex; gap: 20px;">
                    <div><strong>کل لاگ‌ها:</strong> <?php echo $total_logs; ?></div>
                    <div><strong>دسترسی به داشبورد:</strong> <span style="color: #007cba;"><?php echo $access_count; ?></span></div>
                    <div><strong>آپلود فایل:</strong> <span style="color: #28a745;"><?php echo $upload_count; ?></span></div>
                </div>
            </div>

            <div class="log-filters" style="margin-bottom: 20px;">
                <form method="get" action="">
                    <input type="hidden" name="page" value="wc-settings">
                    <input type="hidden" name="tab" value="wc_admin_dashboard">
                    <select name="log_type" onchange="this.form.submit()">
                        <option value="">همه فعالیت‌ها</option>
                        <option value="access" <?php echo ($log_type === 'access') ? 'selected' : ''; ?>>دسترسی به داشبورد</option>
                        <option value="upload" <?php echo ($log_type === 'upload') ? 'selected' : ''; ?>>آپلود فایل</option>
                    </select>
                    <input type="text" name="search" placeholder="جستجو در نام کاربر یا فایل..." value="<?php echo esc_attr($search); ?>" style="margin-left: 10px;">
                    <input type="text" name="ip_search" placeholder="جستجو بر اساس آیپی..." value="<?php echo esc_attr($ip_search); ?>" style="margin-left: 10px;">
                    <input type="submit" value="فیلتر" class="button">
                    <?php if ($log_type || $search || $ip_search): ?>
                        <a href="?page=wc-settings&tab=wc_admin_dashboard" class="button">پاک کردن فیلتر</a>
                    <?php endif; ?>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>نوع فعالیت</th>
                        <th>نام فایل</th>
                        <th>نتیجه</th>
                        <th>آدرس IP</th>
                        <th>زمان دسترسی</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6">لاگی یافت نشد.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->display_name . ' (' . $log->user_login . ')'); ?></td>
                                <td>
                                    <?php
                                    if ($log->log_type === 'access') {
                                        echo '<span style="color: #007cba;">🔵 دسترسی به داشبورد</span>';
                                    } elseif ($log->log_type === 'upload') {
                                        echo '<span style="color: #28a745;">🟢 آپلود فایل</span>';
                                    } else {
                                        echo esc_html($log->log_type);
                                    }
                                    ?>
                                </td>
                                <td><?php echo $log->file_name ? esc_html($log->file_name) : '-'; ?></td>
                                <td><?php echo $log->result ? esc_html($log->result) : '-'; ?></td>
                                <td><?php echo esc_html($log->ip_address); ?></td>
                                <td><?php echo esc_html($log->access_time); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function logs_page() {
        $log_type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        $logs = WC_Admin_Logger::get_logs(50, $log_type, $search);
        $total_logs = WC_Admin_Logger::get_total_logs();
        $access_count = WC_Admin_Logger::get_logs_count_by_type('access');
        $upload_count = WC_Admin_Logger::get_logs_count_by_type('upload');
        ?>
        <div class="wrap">
            <h1>لاگ‌های داشبورد سفارشی</h1>
            
            <div class="log-stats" style="background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <h3>آمار کلی</h3>
                <div style="display: flex; gap: 20px;">
                    <div><strong>کل لاگ‌ها:</strong> <?php echo $total_logs; ?></div>
                    <div><strong>دسترسی به داشبورد:</strong> <span style="color: #007cba;"><?php echo $access_count; ?></span></div>
                    <div><strong>آپلود فایل:</strong> <span style="color: #28a745;"><?php echo $upload_count; ?></span></div>
                </div>
            </div>

            <div class="log-filters" style="margin-bottom: 20px;">
                <form method="get" action="">
                    <input type="hidden" name="page" value="custom-dashboard-logs">
                    <select name="log_type" onchange="this.form.submit()">
                        <option value="">همه فعالیت‌ها</option>
                        <option value="access" <?php echo (isset($_GET['log_type']) && $_GET['log_type'] === 'access') ? 'selected' : ''; ?>>دسترسی به داشبورد</option>
                        <option value="upload" <?php echo (isset($_GET['log_type']) && $_GET['log_type'] === 'upload') ? 'selected' : ''; ?>>آپلود فایل</option>
                    </select>
                    <input type="text" name="search" placeholder="جستجو در نام کاربر یا فایل..." value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>" style="margin-left: 10px;">
                    <input type="submit" value="فیلتر" class="button">
                    <?php if (isset($_GET['log_type']) || isset($_GET['search'])): ?>
                        <a href="?page=custom-dashboard-logs" class="button">پاک کردن فیلتر</a>
                    <?php endif; ?>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>نوع فعالیت</th>
                        <th>نام فایل</th>
                        <th>نتیجه</th>
                        <th>آدرس IP</th>
                        <th>زمان دسترسی</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6">لاگی یافت نشد.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->display_name . ' (' . $log->user_login . ')'); ?></td>
                                <td>
                                    <?php
                                    if ($log->log_type === 'access') {
                                        echo '<span style="color: #007cba;">🔵 دسترسی به داشبورد</span>';
                                    } elseif ($log->log_type === 'upload') {
                                        echo '<span style="color: #28a745;">🟢 آپلود فایل</span>';
                                    } else {
                                        echo esc_html($log->log_type);
                                    }
                                    ?>
                                </td>
                                <td><?php echo $log->file_name ? esc_html($log->file_name) : '-'; ?></td>
                                <td><?php echo $log->result ? esc_html($log->result) : '-'; ?></td>
                                <td><?php echo esc_html($log->ip_address); ?></td>
                                <td><?php echo esc_html($log->access_time); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}