<?php
class WC_Manager_Shortcodes
{

    public function __construct()
    {
        add_shortcode('wc_manager_login', array($this, 'login_form_shortcode'));
        add_shortcode('wc_manager_login_modal', array($this, 'login_modal_shortcode'));
        add_action('wp_ajax_wc_manager_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_wc_manager_login', array($this, 'handle_login'));
        add_action('wp_ajax_wc_manager_register', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_wc_manager_register', array($this, 'handle_register'));
    }

    public function login_form_shortcode($atts)
    {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            return '<div class="wc_manager-welcome">خوش آمدید، ' . $current_user->display_name . '! <a href="' . wp_logout_url() . '">خروج</a></div>';
        }

        ob_start();
?>
        <div class="wc_manager-login-form">
            <form id="wc_manager-login-form" method="post">
                <p>
                    <label for="wc_manager-username">نام کاربری یا ایمیل:</label>
                    <input type="text" id="wc_manager-username" name="username" required>
                </p>
                <p>
                    <label for="wc_manager-password">رمز عبور:</label>
                    <input type="password" id="wc_manager-password" name="password" required>
                </p>
                <p>
                    <input type="submit" value="ورود">
                    <span id="wc_manager-login-message"></span>
                </p>
            </form>
        </div>
    <?php
        return ob_get_clean();
    }

    public function login_modal_shortcode($atts)
    {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            return '<div class="wc_manager-welcome">خوش آمدید، ' . $current_user->display_name . '! <a href="' . wp_logout_url() . '">خروج</a></div>';
        }

        ob_start();
    ?>
        <button id="wc_manager-login-modal-btn" class="wc_manager-modal-btn">ورود</button>

        <div id="wc_manager-modal" class="wc_manager-modal">
            <div class="wc_manager-modal-content">
                <span class="wc_manager-close">&times;</span>
                <div class="wc_manager-modal-tabs">
                    <button class="wc_manager-tab-btn active" data-tab="login">ورود</button>
                </div>
                <div id="wc_manager-login-tab" class="wc_manager-tab-content active">
                    <h3>ورود به حساب کاربری</h3>
                    <form id="wc_manager-modal-login-form" method="post">
                        <p>
                            <label for="wc_manager-modal-username">نام کاربری یا ایمیل:</label>
                            <input type="text" id="wc_manager-modal-username" name="username" required>
                        </p>
                        <p>
                            <label for="wc_manager-modal-password">رمز عبور:</label>
                            <input type="password" id="wc_manager-modal-password" name="password" required>
                        </p>
                        <p>
                            <input type="submit" value="ورود">
                        </p>
                    </form>
                </div>
                <div id="wc_manager-modal-message"></div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    public function handle_login()
    {
        check_ajax_referer('wc_manager_ajax_nonce', 'nonce');

        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];

        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true
        );

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            wp_send_json_error($user->get_error_message());
        } else {
            wp_send_json_success('ورود با موفقیت انجام شد.');
        }
    }
}
