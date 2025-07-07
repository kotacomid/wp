<?php
/**
 * Background processor using Action Scheduler
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Background_Processor {
    
    private $database;
    private $api_handler;
    private $batch_size = 5;
    
    public function __construct() {
        $this->database = new KotacomAI_Database();
        $this->api_handler = new KotacomAI_API_Handler();
        $this->batch_size = get_option('kotacom_ai_queue_batch_size', 5);
        
        $this->init();
    }
    
    /**
     * Initialize background processor
     */
    private function init() {
        // Hook into Action Scheduler
        add_action('kotacom_ai_process_batch', array($this, 'process_batch'), 10, 1);
        add_action('kotacom_ai_process_single_item', array($this, 'process_single_item'), 10, 1);
        
        // Schedule recurring batch processor
        add_action('init', array($this, 'schedule_batch_processor'));
        
        // AJAX hooks for progress tracking
        add_action('wp_ajax_kotacom_get_processing_status', array($this, 'ajax_get_processing_status'));
    }
    
    /**
     * Schedule batch processor using Action Scheduler
     */
    public function schedule_batch_processor() {
        if (!function_exists('as_has_scheduled_action')) {
            return; // Action Scheduler not available
        }
        
        // Schedule recurring batch processing every 2 minutes
        if (!as_has_scheduled_action('kotacom_ai_process_batch')) {
            as_schedule_recurring_action(
                time(),
                120, // 2 minutes
                'kotacom_ai_process_batch',
                array(),
                'kotacom-ai'
            );
        }
    }
    
    /**
     * Add items to queue for processing
     */
    public function add_to_queue($keywords, $prompt_template, $parameters, $post_settings) {
        $batch_id = uniqid('batch_');
        $total_items = count($keywords);
        
        // Create batch record
        $this->create_batch_record($batch_id, $total_items);
        
        foreach ($keywords as $keyword) {
            $queue_id = $this->database->add_to_queue($keyword, $prompt_template, $parameters, $post_settings);
            
            if ($queue_id) {
                // Update queue item with batch ID
                $this->database->update_queue_batch_id($queue_id, $batch_id);
                
                // Schedule individual processing with delay to prevent rate limiting
                $delay = array_search($keyword, $keywords) * 10; // 10 seconds between items
                
                as_schedule_single_action(
                    time() + $delay,
                    'kotacom_ai_process_single_item',
                    array('queue_id' => $queue_id),
                    'kotacom-ai'
                );
            }
        }
        
        return array(
            'success' => true,
            'batch_id' => $batch_id,
            'total_items' => $total_items,
            'message' => sprintf(__('%d items added to processing queue', 'kotacom-ai'), $total_items)
        );
    }
    
    /**
     * Process batch of items
     */
    public function process_batch() {
        $pending_items = $this->database->get_pending_queue_items($this->batch_size);
        
        if (empty($pending_items)) {
            return;
        }
        
        foreach ($pending_items as $item) {
            // Schedule individual item processing
            as_schedule_single_action(
                time(),
                'kotacom_ai_process_single_item',
                array('queue_id' => $item['id']),
                'kotacom-ai'
            );
        }
    }
    
    /**
     * Process single queue item
     */
    public function process_single_item($queue_id) {
        $item = $this->database->get_queue_item_by_id($queue_id);
        
        if (!$item || $item['status'] !== 'pending') {
            return;
        }
        
        // Update status to processing
        $this->database->update_queue_item_status($queue_id, 'processing');
        
        try {
            $parameters = json_decode($item['parameters'], true);
            $post_settings = json_decode($item['post_settings'], true);
            
            // Replace {keyword} in prompt template
            $prompt = str_replace('{keyword}', $item['keyword'], $item['prompt_template']);
            
            // Generate content using AI API
            $result = $this->api_handler->generate_content($prompt, $parameters);
            
            if ($result['success']) {
                // Create WordPress post
                $post_id = $this->create_wordpress_post($item['keyword'], $result['content'], $post_settings);
                
                if ($post_id) {
                    $this->database->update_queue_item_status($queue_id, 'completed');
                    $this->database->update_queue_item_post_id($queue_id, $post_id);
                    
                    // Update batch progress
                    $this->update_batch_progress($item['batch_id']);
                    
                    // Fire action hook
                    do_action('kotacom_ai_item_processed', $item, $post_id);
                } else {
                    $this->database->update_queue_item_status($queue_id, 'failed', __('Failed to create WordPress post', 'kotacom-ai'));
                }
            } else {
                $this->database->update_queue_item_status($queue_id, 'failed', $result['error']);
            }
            
        } catch (Exception $e) {
            $this->database->update_queue_item_status($queue_id, 'failed', $e->getMessage());
            
            if (defined('KOTACOM_AI_DEBUG') && KOTACOM_AI_DEBUG) {
                error_log('Kotacom AI Processing Error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Create WordPress post from generated content
     */
    private function create_wordpress_post($keyword, $content, $post_settings) {
        // Generate title from keyword or content
        $title = $this->generate_post_title($keyword, $content);
        
        // Prepare post data
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $post_settings['post_status'] ?: 'draft',
            'post_type' => $post_settings['post_type'] ?: 'post',
            'post_author' => get_current_user_id() ?: 1,
            'meta_input' => array(
                'kotacom_ai_generated' => true,
                'kotacom_ai_keyword' => $keyword,
                'kotacom_ai_generated_at' => current_time('mysql')
            )
        );
        
        // Add categories
        if (!empty($post_settings['categories'])) {
            $post_data['post_category'] = array_map('intval', $post_settings['categories']);
        }
        
        // Filter post data
        $post_data = apply_filters('kotacom_ai_post_data', $post_data, $keyword, $content, $post_settings);
        
        // Insert post
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Add tags
            if (!empty($post_settings['tags'])) {
                wp_set_post_tags($post_id, $post_settings['tags']);
            }
            
            return $post_id;
        }
        
        return false;
    }
    
    /**
     * Generate post title from keyword or content
     */
    private function generate_post_title($keyword, $content) {
        // Try to extract title from content (look for first heading)
        if (preg_match('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $content, $matches)) {
            return wp_strip_all_tags($matches[1]);
        }
        
        // Try to get first sentence
        $sentences = preg_split('/[.!?]+/', wp_strip_all_tags($content));
        if (!empty($sentences[0])) {
            $title = trim($sentences[0]);
            if (strlen($title) > 10 && strlen($title) < 100) {
                return $title;
            }
        }
        
        // Fallback to keyword-based title
        return ucwords(str_replace(array('-', '_'), ' ', $keyword));
    }
    
    /**
     * Create batch record for progress tracking
     */
    private function create_batch_record($batch_id, $total_items) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kotacom_batches';
        
        return $wpdb->insert(
            $table,
            array(
                'batch_id' => $batch_id,
                'total_items' => $total_items,
                'completed_items' => 0,
                'failed_items' => 0,
                'status' => 'processing',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%d', '%d', '%s', '%s')
        );
    }
    
    /**
     * Update batch progress
     */
    private function update_batch_progress($batch_id) {
        if (empty($batch_id)) {
            return;
        }
        
        global $wpdb;
        
        $queue_table = $wpdb->prefix . 'kotacom_queue';
        $batch_table = $wpdb->prefix . 'kotacom_batches';
        
        // Get current batch stats
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
             FROM {$queue_table} 
             WHERE batch_id = %s",
            $batch_id
        ));
        
        if ($stats) {
            $status = 'processing';
            if ($stats->completed + $stats->failed >= $stats->total) {
                $status = 'completed';
            }
            
            $wpdb->update(
                $batch_table,
                array(
                    'completed_items' => $stats->completed,
                    'failed_items' => $stats->failed,
                    'status' => $status,
                    'updated_at' => current_time('mysql')
                ),
                array('batch_id' => $batch_id),
                array('%d', '%d', '%s', '%s'),
                array('%s')
            );
        }
    }
    
    /**
     * Get processing status via AJAX
     */
    public function ajax_get_processing_status() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'kotacom-ai'));
        }
        
        $batch_id = sanitize_text_field($_POST['batch_id'] ?? '');
        
        if (empty($batch_id)) {
            wp_send_json_error(array('message' => __('Batch ID required', 'kotacom-ai')));
        }
        
        $status = $this->get_batch_status($batch_id);
        
        wp_send_json_success($status);
    }
    
    /**
     * Get batch processing status
     */
    public function get_batch_status($batch_id) {
        global $wpdb;
        
        $batch_table = $wpdb->prefix . 'kotacom_batches';
        $queue_table = $wpdb->prefix . 'kotacom_queue';
        
        // Get batch info
        $batch = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$batch_table} WHERE batch_id = %s",
            $batch_id
        ), ARRAY_A);
        
        if (!$batch) {
            return array('error' => 'Batch not found');
        }
        
        // Get detailed queue items
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT keyword, status, error_message, post_id FROM {$queue_table} WHERE batch_id = %s ORDER BY created_at ASC",
            $batch_id
        ), ARRAY_A);
        
        return array(
            'batch_id' => $batch_id,
            'total_items' => $batch['total_items'],
            'completed_items' => $batch['completed_items'],
            'failed_items' => $batch['failed_items'],
            'status' => $batch['status'],
            'progress_percentage' => round(($batch['completed_items'] + $batch['failed_items']) / $batch['total_items'] * 100, 2),
            'items' => $items,
            'created_at' => $batch['created_at'],
            'updated_at' => $batch['updated_at']
        );
    }
    
    /**
     * Start batch processing for a specific batch
     */
    public function start_batch_processing($batch_id) {
        global $wpdb;
        
        $queue_table = $wpdb->prefix . 'kotacom_queue';
        
        // Get all pending items for this batch
        $pending_items = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$queue_table} WHERE batch_id = %s AND status = 'pending'",
            $batch_id
        ));
        
        if (empty($pending_items)) {
            return false;
        }
        
        // Schedule processing for each item with staggered timing to prevent rate limiting
        $delay = 0;
        foreach ($pending_items as $item) {
            as_schedule_single_action(
                time() + $delay,
                'kotacom_ai_process_single_item',
                array('queue_id' => $item->id),
                'kotacom-ai'
            );
            $delay += 10; // 10 seconds between each item
        }
        
        return count($pending_items);
    }
    
    /**
     * Get overall queue statistics
     */
    public function get_queue_stats() {
        return $this->database->get_queue_status();
    }
    
    /**
     * Retry failed items
     */
    public function retry_failed_items($batch_id = null) {
        global $wpdb;
        
        $queue_table = $wpdb->prefix . 'kotacom_queue';
        
        $where_clause = "status = 'failed'";
        $where_values = array();
        
        if ($batch_id) {
            $where_clause .= " AND batch_id = %s";
            $where_values[] = $batch_id;
        }
        
        // Get failed items
        $failed_items = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$queue_table} WHERE {$where_clause}",
            $where_values
        ));
        
        foreach ($failed_items as $item) {
            // Reset status to pending
            $wpdb->update(
                $queue_table,
                array('status' => 'pending', 'error_message' => ''),
                array('id' => $item->id),
                array('%s', '%s'),
                array('%d')
            );
            
            // Reschedule processing
            as_schedule_single_action(
                time() + 5, // 5 seconds delay
                'kotacom_ai_process_single_item',
                array('queue_id' => $item->id),
                'kotacom-ai'
            );
        }
        
        return count($failed_items);
    }
    
    /**
     * Cancel batch processing
     */
    public function cancel_batch($batch_id) {
        global $wpdb;
        
        $queue_table = $wpdb->prefix . 'kotacom_queue';
        $batch_table = $wpdb->prefix . 'kotacom_batches';
        
        // Cancel pending items
        $wpdb->update(
            $queue_table,
            array('status' => 'cancelled'),
            array('batch_id' => $batch_id, 'status' => 'pending'),
            array('%s'),
            array('%s', '%s')
        );
        
        // Update batch status
        $wpdb->update(
            $batch_table,
            array('status' => 'cancelled', 'updated_at' => current_time('mysql')),
            array('batch_id' => $batch_id),
            array('%s', '%s'),
            array('%s')
        );
        
        // Cancel scheduled actions
        as_unschedule_all_actions('kotacom_ai_process_single_item', array('batch_id' => $batch_id), 'kotacom-ai');
        
        return true;
    }
}
