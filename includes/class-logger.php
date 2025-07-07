<?php
/**
 * Lightweight Logger for Kotacom AI (success / fail only)
 */
if (!defined('ABSPATH')) { exit; }

class KotacomAI_Logger {
    public static function add($action, $success, $post_id = null, $message = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'kotacom_ai_logs';
        $wpdb->insert($table, array(
            'ts' => current_time('mysql'),
            'action' => sanitize_text_field($action),
            'success' => $success ? 1 : 0,
            'message' => wp_trim_words($message, 40, ''),
            'post_id' => $post_id ? intval($post_id) : null,
        ), array('%s','%s','%d','%s','%d'));
    }

    public static function get_logs($limit = 100, $success_filter = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'kotacom_ai_logs';
        $sql = "SELECT * FROM {$table}";
        $where = array();
        if($success_filter==='success') $where[] = "success = 1";
        if($success_filter==='fail') $where[] = "success = 0";
        if($where) $sql .= ' WHERE '.implode(' AND ', $where);
        $sql .= ' ORDER BY ts DESC LIMIT %d';
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
}