<?php
/**
 * Simple test script to check dashboard functionality
 * Run this from WordPress root directory
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load WordPress
require_once 'wp-load.php';

// Check if user is logged in
if (!is_user_logged_in()) {
    die('Please log in to WordPress first.');
}

$current_user = wp_get_current_user();
echo "Current user: " . $current_user->display_name . " (ID: " . $current_user->ID . ")<br>\n";

// Check allowed users setting
$allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
echo "Allowed users: " . (empty($allowed_users) ? 'None configured' : implode(', ', $allowed_users)) . "<br>\n";

// Check if current user is allowed
if (empty($allowed_users) || !in_array($current_user->ID, $allowed_users)) {
    echo "ERROR: Current user is not in allowed users list!<br>\n";
    echo "Please go to WooCommerce → Settings → داشبورد ادمین and add user ID " . $current_user->ID . "<br>\n";
} else {
    echo "✓ User is allowed to access dashboard<br>\n";
}

// Check WooCommerce orders
global $wpdb;
$order_count = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->posts}
    WHERE post_type = 'shop_order'
    AND post_status IN ('wc-processing', 'wc-completed', 'wc-pending', 'wc-on-hold', 'wc-cancelled')
");

echo "Total WooCommerce orders: " . $order_count . "<br>\n";

if ($order_count == 0) {
    echo "WARNING: No orders found in WooCommerce. Dashboard will show empty data.<br>\n";
    echo "Please create some test orders to see dashboard data.<br>\n";
} else {
    echo "✓ Orders found - dashboard should show data<br>\n";

    // Get recent orders
    $recent_orders = $wpdb->get_results("
        SELECT ID, post_date, post_status
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
        AND post_status IN ('wc-processing', 'wc-completed', 'wc-pending', 'wc-on-hold', 'wc-cancelled')
        ORDER BY post_date DESC
        LIMIT 5
    ");

    echo "Recent orders:<br>\n";
    foreach ($recent_orders as $order) {
        echo "- Order #{$order->ID} ({$order->post_status}) - {$order->post_date}<br>\n";
    }
}

// Test the dashboard class
if (class_exists('wc_admin_dashboard')) {
    echo "✓ Dashboard class exists<br>\n";

    $dashboard = new wc_admin_dashboard();

    // Test get_orders_stats method
    if (method_exists($dashboard, 'get_orders_stats')) {
        echo "✓ get_orders_stats method exists<br>\n";

        // Simulate AJAX call
        $_POST['action'] = 'get_orders_stats';
        $_POST['nonce'] = wp_create_nonce('wc_admin_dashboard_nonce');

        try {
            $result = $dashboard->get_orders_stats();
            echo "✓ get_orders_stats executed successfully<br>\n";
            echo "Result: " . json_encode($result) . "<br>\n";
        } catch (Exception $e) {
            echo "ERROR in get_orders_stats: " . $e->getMessage() . "<br>\n";
        }
    } else {
        echo "ERROR: get_orders_stats method not found<br>\n";
    }
} else {
    echo "ERROR: wc_admin_dashboard class not found<br>\n";
}

echo "<br>\n=== Test Complete ===";
?>