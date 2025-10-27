<?php
/**
 * Plugin Name: داشبورد ادمین سفارشی
 * Description: داشبورد کوچکی برای اعضا خاص جهت آپلود فایل اکسل و بروزرسانی محصولات و مدیریت سفارشات
 * Version: 1.0
 * Author: Alireza Yaghouti
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/class-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-excel-processor.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Initialize the plugin
function custom_admin_dashboard_init() {
    new Custom_Admin_Dashboard();
    new Custom_Admin_Settings();
    new SMSIR_Shortcodes();
}
add_action('plugins_loaded', 'custom_admin_dashboard_init');

// Activation hook
function custom_admin_dashboard_activate() {
    Custom_Admin_Logger::create_table();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'custom_admin_dashboard_activate');

// Deactivation hook
function custom_admin_dashboard_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'custom_admin_dashboard_deactivate');