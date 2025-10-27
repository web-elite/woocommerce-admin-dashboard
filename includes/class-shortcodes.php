<?php
class SMSIR_Shortcodes {

    public function __construct() {
        add_shortcode('smsir_login', array($this, 'login_form_shortcode'));
        add_shortcode('smsir_login_modal', array($this, 'login_modal_shortcode'));
        add_shortcode('smsir_newsletter', array($this, 'newsletter_shortcode'));
        add_action('wp_ajax_smsir_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_smsir_login', array($this, 'handle_login'));
        add_action('wp_ajax_smsir_register', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_smsir_register', array($this, 'handle_register'));
        add_action('wp_ajax_smsir_newsletter', array($this, 'handle_newsletter'));
        add_action('wp_ajax_nopriv_smsir_newsletter', array($this, 'handle_newsletter'));
    }

    public function login_form_shortcode($atts) {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            return '<div class="smsir-welcome">خوش آمدید، ' . $current_user->display_name . '! <a href="' . wp_logout_url() . '">خروج</a></div>';
        }

        ob_start();
        ?>
        <div class="smsir-login-form">
            <h3>ورود به حساب کاربری</h3>
            <form id="smsir-login-form" method="post">
                <p>
                    <label for="smsir-username">نام کاربری یا ایمیل:</label>
                    <input type="text" id="smsir-username" name="username" required>
                </p>
                <p>
                    <label for="smsir-password">رمز عبور:</label>
                    <input type="password" id="smsir-password" name="password" required>
                </p>
                <p>
                    <input type="submit" value="ورود">
                    <span id="smsir-login-message"></span>
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function login_modal_shortcode($atts) {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            return '<div class="smsir-welcome">خوش آمدید، ' . $current_user->display_name . '! <a href="' . wp_logout_url() . '">خروج</a></div>';
        }

        ob_start();
        ?>
        <button id="smsir-login-modal-btn" class="smsir-modal-btn">ورود / ثبت نام</button>

        <div id="smsir-modal" class="smsir-modal">
            <div class="smsir-modal-content">
                <span class="smsir-close">&times;</span>
                <div class="smsir-modal-tabs">
                    <button class="smsir-tab-btn active" data-tab="login">ورود</button>
                    <button class="smsir-tab-btn" data-tab="register">ثبت نام</button>
                </div>
                <div id="smsir-login-tab" class="smsir-tab-content active">
                    <h3>ورود به حساب کاربری</h3>
                    <form id="smsir-modal-login-form" method="post">
                        <p>
                            <label for="smsir-modal-username">نام کاربری یا ایمیل:</label>
                            <input type="text" id="smsir-modal-username" name="username" required>
                        </p>
                        <p>
                            <label for="smsir-modal-password">رمز عبور:</label>
                            <input type="password" id="smsir-modal-password" name="password" required>
                        </p>
                        <p>
                            <input type="submit" value="ورود">
                        </p>
                    </form>
                </div>
                <div id="smsir-register-tab" class="smsir-tab-content">
                    <h3>ثبت نام حساب جدید</h3>
                    <form id="smsir-modal-register-form" method="post">
                        <p>
                            <label for="smsir-modal-reg-username">نام کاربری:</label>
                            <input type="text" id="smsir-modal-reg-username" name="username" required>
                        </p>
                        <p>
                            <label for="smsir-modal-reg-email">ایمیل:</label>
                            <input type="email" id="smsir-modal-reg-email" name="email" required>
                        </p>
                        <p>
                            <label for="smsir-modal-reg-password">رمز عبور:</label>
                            <input type="password" id="smsir-modal-reg-password" name="password" required>
                        </p>
                        <p>
                            <input type="submit" value="ثبت نام">
                        </p>
                    </form>
                </div>
                <div id="smsir-modal-message"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function newsletter_shortcode($atts) {
        ob_start();
        ?>
        <div class="smsir-newsletter-form">
            <h3>عضویت در خبرنامه</h3>
            <form id="smsir-newsletter-form" method="post">
                <p>
                    <label for="smsir-newsletter-email">ایمیل شما:</label>
                    <input type="email" id="smsir-newsletter-email" name="email" required>
                </p>
                <p>
                    <input type="submit" value="عضویت">
                    <span id="smsir-newsletter-message"></span>
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_login() {
        check_ajax_referer('smsir_ajax_nonce', 'nonce');

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

    public function handle_register() {
        check_ajax_referer('smsir_ajax_nonce', 'nonce');

        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        if (username_exists($username)) {
            wp_send_json_error('این نام کاربری قبلاً استفاده شده است.');
            return;
        }

        if (email_exists($email)) {
            wp_send_json_error('این ایمیل قبلاً ثبت شده است.');
            return;
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        } else {
            wp_send_json_success('ثبت نام با موفقیت انجام شد.');
        }
    }

    public function handle_newsletter() {
        check_ajax_referer('smsir_ajax_nonce', 'nonce');

        $email = sanitize_email($_POST['email']);

        if (!is_email($email)) {
            wp_send_json_error('ایمیل معتبر نیست.');
            return;
        }

        // Here you would typically save to a newsletter service
        // For now, we'll just simulate success
        wp_send_json_success('با موفقیت در خبرنامه عضو شدید.');
    }
}