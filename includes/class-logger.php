<?php
/**
 * Enhanced Logger for Kotacom AI with statistics and filtering
 */
if (!defined('ABSPATH')) { exit; }

class KotacomAI_Logger {
    
    /**
     * Add log entry
     */
    public static function add($action, $success, $post_id = null, $message = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'kotacom_ai_logs';
        
        $result = $wpdb->insert($table, array(
            'ts' => current_time('mysql'),
            'action' => sanitize_text_field($action),
            'success' => $success ? 1 : 0,
            'message' => wp_trim_words($message, 50, '...'),
            'post_id' => $post_id ? intval($post_id) : null,
        ), array('%s','%s','%d','%s','%d'));
        
        // Auto cleanup old logs (keep last 1000 entries)
        if ($result && rand(1, 50) === 1) {
            self::cleanup_old_logs();
        }
        
        return $result;
    }

    /**
     * Get logs with enhanced filtering
     */
    public static function get_logs($limit = 100, $success_filter = '', $action_filter = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'kotacom_ai_logs';
        
        $sql = "SELECT * FROM {$table}";
        $where = array();
        
        if($success_filter === 'success') $where[] = "success = 1";
        if($success_filter === 'fail') $where[] = "success = 0";
        if(!empty($action_filter)) $where[] = $wpdb->prepare("action = %s", $action_filter);
        
        if($where) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }
        
        $sql .= ' ORDER BY ts DESC LIMIT %d';
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
    
    /**
     * Get statistics for dashboard
     */
    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'kotacom_ai_logs';
        
        $stats = array(
            'total_logs' => 0,
            'total_success' => 0,
            'total_failed' => 0,
            'success_rate' => 0,
            'actions' => array()
        );
        
        // Total counts
        $counts = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(success) as success,
                COUNT(*) - SUM(success) as failed
            FROM {$table}
        ");
        
        if ($counts) {
            $stats['total_logs'] = intval($counts->total);
            $stats['total_success'] = intval($counts->success);
            $stats['total_failed'] = intval($counts->failed);
            
            if ($stats['total_logs'] > 0) {
                $stats['success_rate'] = ($stats['total_success'] / $stats['total_logs']) * 100;
            }
        }
        
        // Action breakdown
        $actions = $wpdb->get_results("
            SELECT action, COUNT(*) as count 
            FROM {$table} 
            GROUP BY action 
            ORDER BY count DESC 
            LIMIT 20
        ");
        
        foreach ($actions as $action) {
            $stats['actions'][$action->action] = intval($action->count);
        }
        
        return $stats;
    }
    
    /**
     * Get recent activity for dashboard
     */
    public static function get_recent_activity($limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'kotacom_ai_logs';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table} 
            ORDER BY ts DESC 
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Clear all logs
     */
    public static function clear_all_logs() {
        global $wpdb;
        $table = $wpdb->prefix . 'kotacom_ai_logs';
        return $wpdb->query("TRUNCATE TABLE {$table}");
    }
    
    /**
     * Auto cleanup old logs (keep latest 1000 entries)
     */
    public static function cleanup_old_logs() {
        global $wpdb;
        $table = $wpdb->prefix . 'kotacom_ai_logs';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        
        if ($count > 1000) {
            $wpdb->query("
                DELETE FROM {$table} 
                WHERE id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM {$table} 
                        ORDER BY ts DESC 
                        LIMIT 1000
                    ) as recent_logs
                )
            ");
        }
    }
    
    /**
     * Get logs by action type
     */
    public static function get_logs_by_action($action, $limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'kotacom_ai_logs';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table} 
            WHERE action = %s 
            ORDER BY ts DESC 
            LIMIT %d
        ", $action, $limit));
    }
    
    /**
     * Get logs for specific post
     */
    public static function get_logs_for_post($post_id, $limit = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'kotacom_ai_logs';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table} 
            WHERE post_id = %d 
            ORDER BY ts DESC 
            LIMIT %d
        ", $post_id, $limit));
    }
}