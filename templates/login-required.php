<?php
get_header();

// Enqueue WooCommerce scripts for login form
wp_enqueue_script('woocommerce');
wp_enqueue_script('wc-password-strength-meter');
wp_enqueue_style('woocommerce-general');
wp_enqueue_style('woocommerce-layout');
wp_enqueue_style('woocommerce-smallscreen');
?>
<style>
    /* Login Required Styles */
    .login-required-container {
        max-width: 520px !important;
        margin: 5rem auto !important;
        padding: 3.5rem !important;
        text-align: center !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 28px !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.06) !important;
        position: relative !important;
        overflow: hidden !important;
    }

    .login-required-container::before {
        content: "" !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        background: linear-gradient(135deg,
                rgba(102, 126, 234, 0.02) 0%,
                rgba(118, 75, 162, 0.02) 100%) !important;
        border-radius: 28px !important;
        pointer-events: none !important;
    }

    .login-required-container h1 {
        color: #2d3748 !important;
        font-size: 2.25rem !important;
        font-weight: 700 !important;
        margin-bottom: 1.5rem !important;
        position: relative !important;
        z-index: 1 !important;
    }

    .login-required-container p {
        margin: 1rem 0 !important;
        color: #718096 !important;
        font-size: 1.25rem !important;
        position: relative !important;
        z-index: 1 !important;
    }

    /* WooCommerce Login Form Styles */
    .woocommerce-form-login {
        position: relative !important;
        z-index: 1 !important;
    }

    .woocommerce-form-login .form-row {
        margin-bottom: 1.5rem !important;
    }

    .woocommerce-form-login label {
        display: block !important;
        font-weight: 600 !important;
        color: #4a5568 !important;
        font-size: 1rem !important;
        margin-bottom: 0.5rem !important;
    }

    .woocommerce-form-login input[type="text"],
    .woocommerce-form-login input[type="password"] {
        width: 100% !important;
        padding: 1.25rem 1.5rem !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 16px !important;
        background: rgba(255, 255, 255, 0.8) !important;
        backdrop-filter: blur(10px) !important;
        font-size: 1rem !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
    }

    .woocommerce-form-login input[type="text"]:focus,
    .woocommerce-form-login input[type="password"]:focus {
        outline: none !important;
        border-color: #667eea !important;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.08),
            0 4px 16px rgba(0, 0, 0, 0.06) !important;
        transform: translateY(-2px) !important;
    }

    .woocommerce-form-login .woocommerce-form-login__submit {
        width: 100% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        padding: 1.25rem 2.5rem !important;
        border: none !important;
        border-radius: 16px !important;
        cursor: pointer !important;
        font-size: 1.125rem !important;
        font-weight: 600 !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.25),
            0 2px 4px rgba(0, 0, 0, 0.1) !important;
        position: relative !important;
        overflow: hidden !important;
    }

    .woocommerce-form-login .woocommerce-form-login__submit:hover {
        transform: translateY(-3px) scale(1.02) !important;
        box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3),
            0 4px 8px rgba(0, 0, 0, 0.15) !important;
    }

    .woocommerce-form-login .woocommerce-form-login__rememberme {
        display: flex !important;
        align-items: center !important;
        margin-bottom: 1.5rem !important;
    }

    .woocommerce-form-login .woocommerce-form-login__rememberme input {
        margin-right: 0.5rem !important;
    }

    .woocommerce-form-login .woocommerce-form-login__rememberme label {
        margin: 0 !important;
        font-weight: 500 !important;
    }

    .woocommerce-notice {
        margin-bottom: 1.5rem !important;
        padding: 1rem !important;
        border-radius: 8px !important;
        position: relative !important;
        z-index: 1 !important;
    }

    .woocommerce-notice.woocommerce-message {
        background: rgba(198, 246, 213, 0.8) !important;
        border: 1px solid rgba(34, 197, 94, 0.3) !important;
        color: #166534 !important;
    }

    .woocommerce-notice.woocommerce-error {
        background: rgba(254, 202, 202, 0.8) !important;
        border: 1px solid rgba(239, 68, 68, 0.3) !important;
        color: #991b1b !important;
    }

    @media (max-width: 480px) {
        .login-required-container {
            margin: 2rem auto !important;
            padding: 2rem !important;
        }

        .login-required-container h1 {
            font-size: 1.875rem !important;
        }
    }
</style>
<div class="login-required-container">
    <h1>دسترسی به داشبورد</h1>
    <p>برای دسترسی به داشبورد سفارشی، لطفاً وارد حساب کاربری خود شوید.</p>

    <?php woocommerce_login_form(array('redirect' => home_url('/admin-dashboard/'))); ?>
</div>
<?php
get_footer();
?>