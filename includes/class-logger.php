<?php
class WC_Admin_Logger {

    public static function log_access($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'custom_dashboard_logs';

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'log_type' => 'access',
                'ip_address' => self::get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'access_time' => current_time('mysql')
            )
        );
    }

    public static function log_upload($user_id, $file_name, $file_path, $result) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'custom_dashboard_logs';

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'log_type' => 'upload',
                'file_name' => $file_name,
                'file_path' => $file_path,
                'result' => $result,
                'ip_address' => self::get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'access_time' => current_time('mysql')
            )
        );
    }

    public static function log_order_status_change($user_id, $order_id, $new_status) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'custom_dashboard_logs';

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'log_type' => 'status_change',
                'file_name' => 'Order #' . $order_id,
                'result' => 'Status changed to: ' . $new_status,
                'ip_address' => self::get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'access_time' => current_time('mysql')
            )
        );
    }

    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'custom_dashboard_logs';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            log_type varchar(50) NOT NULL DEFAULT 'upload',
            file_name varchar(255) DEFAULT NULL,
            file_path varchar(500) DEFAULT NULL,
            result text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            access_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function get_logs($limit = 50, $type = '', $search = '', $ip_search = '') {
        global $wpdb;

        $table_name = $wpdb->prefix . 'custom_dashboard_logs';

        $where = array();
        $where_values = array();

        if (!empty($type)) {
            $where[] = "l.log_type = %s";
            $where_values[] = $type;
        }

        if (!empty($search)) {
            $where[] = "(u.user_login LIKE %s OR u.display_name LIKE %s OR l.file_name LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        if (!empty($ip_search)) {
            $where[] = "l.ip_address LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($ip_search) . '%';
        }

        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = $wpdb->prepare(
            "SELECT l.*, u.user_login, u.display_name
             FROM $table_name l
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             $where_clause
             ORDER BY l.access_time DESC
             LIMIT %d",
            array_merge($where_values, array($limit))
        );

        return $wpdb->get_results($sql);
    }

    public static function get_total_logs() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'custom_dashboard_logs';

        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    public static function get_logs_count_by_type($type) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'custom_dashboard_logs';

        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE log_type = %s", $type));
    }

    private static function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (like X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}