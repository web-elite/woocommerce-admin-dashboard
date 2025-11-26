<?php

/**
 * Plugin Name: داشبورد مدیریتی مجزا ووکامرس
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
require_once plugin_dir_path(__FILE__) . 'includes/jdf.php';
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Include WooCommerce admin settings if not already loaded
if (!function_exists('woocommerce_admin_fields') && class_exists('WooCommerce')) {
    require_once WC()->plugin_path() . '/includes/admin/class-wc-admin-settings.php';
}

// Initialize the plugin
function wc_admin_dashboard_init()
{
    new wc_admin_dashboard();
    new Custom_Admin_Settings();
    new WC_Manager_Shortcodes();
}
add_action('plugins_loaded', 'wc_admin_dashboard_init');

// Activation hook
function wc_admin_dashboard_activate()
{
    WC_Admin_Logger::create_table();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wc_admin_dashboard_activate');

// Deactivation hook
function wc_admin_dashboard_deactivate()
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wc_admin_dashboard_deactivate');

/**
 * Add setting links.
 *
 * Add a link to the settings page on the plugins.php page.
 *
 * @since 1.0.0
 *
 * @param  array  $links List of existing plugin action links.
 * @return array         List of modified plugin action links.
 */
function wc_admin_setting_action_links($links)
{

    $links = array_merge(array(
        '<a href="' . esc_url(admin_url('/admin.php?page=wc-settings&tab=wc_admin_dashboard')) . '">' . __('تنظیمات', 'textdomain') . '</a>'
    ), $links);

    return $links;
}
add_action('plugin_action_links_' . plugin_basename(__FILE__), 'wc_admin_setting_action_links');
