<?php
class Custom_Admin_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'تنظیمات داشبورد سفارشی',
            'داشبورد سفارشی',
            'manage_woocommerce',
            'custom-dashboard-settings',
            array($this, 'settings_page')
        );
        add_submenu_page(
            'woocommerce',
            'لاگ‌های آپلود',
            'لاگ‌های آپلود',
            'manage_woocommerce',
            'custom-dashboard-logs',
            array($this, 'logs_page')
        );
    }

    public function register_settings() {
        register_setting('custom_dashboard_settings', 'allowed_users');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>تنظیمات داشبورد ادمین سفارشی</h1>
            <form method="post" action="options.php">
                <?php settings_fields('custom_dashboard_settings'); ?>
                <?php do_settings_sections('custom_dashboard_settings'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">کاربران مجاز</th>
                        <td>
                            <select name="allowed_users[]" multiple="multiple" style="width: 300px; height: 200px;">
                                <?php
                                $users = get_users(['role' => 'administrator']);
                                $allowed_users = get_option('allowed_users', array());
                                foreach ($users as $user) {
                                    $selected = in_array($user->ID, $allowed_users) ? 'selected' : '';
                                    echo '<option value="' . $user->ID . '" ' . $selected . '>' . $user->user_login . ' (' . $user->display_name . ')</option>';
                                }
                                ?>
                            </select>
                            <p class="description">کاربرانی را که می‌توانند به داشبورد سفارشی دسترسی داشته باشند انتخاب کنید. برای انتخاب چند کاربر کلید Ctrl (یا Cmd در مک) را نگه دارید.</p>

                            <div style="margin-top: 10px;">
                                <strong>کاربران انتخاب شده:</strong>
                                <?php if (!empty($allowed_users)): ?>
                                    <ul style="margin: 5px 0;">
                                        <?php 
                                        foreach ($allowed_users as $user_id) {
                                            $user = get_user_by('ID', $user_id);
                                            if ($user) {
                                                echo '<li>' . esc_html($user->user_login . ' (' . $user->display_name . ')') . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>
                                <?php else: ?>
                                    <p><em>هیچ کاربری انتخاب نشده است.</em></p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function logs_page() {
        $log_type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        $logs = Custom_Admin_Logger::get_logs(50, $log_type, $search);
        $total_logs = Custom_Admin_Logger::get_total_logs();
        $access_count = Custom_Admin_Logger::get_logs_count_by_type('access');
        $upload_count = Custom_Admin_Logger::get_logs_count_by_type('upload');
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