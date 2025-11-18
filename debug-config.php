// Add this to your wp-config.php file to enable debugging
// define('WP_DEBUG', true);
// define('WP_DEBUG_LOG', true);
// define('WP_DEBUG_DISPLAY', false);

// Temporary debug logging for dashboard
if (!function_exists('write_dashboard_log')) {
    function write_dashboard_log($message) {
        $log_file = WP_CONTENT_DIR . '/dashboard-debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    }
}