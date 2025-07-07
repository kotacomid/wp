<?php
/**
 * Queue processor class for background content generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Queue_Processor {
    
    private $database;
    private $api_handler;
    
    public function __construct() {
        $this->database = new KotacomAI_Database();
        $this->api_handler = new KotacomAI_API_Handler();
    }
    
    /**
     * Process queue items
     */
    public function process_queue() {
        // Prevent multiple instances running
        if (get_transient('kotacom_ai_queue_processing')) {
            return;
        }
        
        // Set processing flag
        set_transient('kotacom_ai_queue_processing', true, 300); // 5 minutes
        
        try {
            $batch_size = get_option('kotacom_ai_queue_batch_size', 5);
            $items = $this->database->get_pending_queue_items($batch_size);
            
            if (empty($items)) {
                delete_transient('kotacom_ai_queue_processing');
                return;
            }
            
            foreach ($items as $item) {
                $this->process_queue_item($item);
                
                // Add small delay between items to prevent rate limiting
                sleep(1);
            }
            
        } catch (Exception $e) {
            if (KOTACOM_AI_DEBUG) {
                error_log('Kotacom AI Queue Error: ' . $e->getMessage());
            }
        }
        
        // Remove processing flag
        delete_transient('kotacom_ai_queue_processing');
    }
    
    /**
     * Process single queue item
     */
    private function process_queue_item($item) {
        // Update status to processing
        $this->database->update_queue_item_status($item['id'], 'processing');
        
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
                    $this->database->update_queue_item_status($item['id'], 'completed');
                    
                    // Fire action hook
                    do_action('kotacom_ai_queue_item_processed', $item, $post_id);
                } else {
                    $this->database->update_queue_item_status($item['id'], 'failed', __('Failed to create WordPress post', 'kotacom-ai'));
                }
            } else {
                $this->database->update_queue_item_status($item['id'], 'failed', $result['error']);
            }
            
        } catch (Exception $e) {
            $this->database->update_queue_item_status($item['id'], 'failed', $e->getMessage());
            
            if (KOTACOM_AI_DEBUG) {
                error_log('Kotacom AI Queue Item Error: ' . $e->getMessage());
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
            
            // Fire action hook
            do_action('kotacom_ai_after_content_generation', $post_id, $keyword, $content, $post_settings);
            
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
     * Retry failed queue items
     */
    public function retry_failed_items() {
        return $this->database->retry_failed_items();
    }
    
    /**
     * Clean old queue items
     */
    public function clean_old_items($days = 30) {
        return $this->database->clean_old_queue_items($days);
    }
    
    /**
     * Get queue statistics
     */
    public function get_queue_stats() {
        return $this->database->get_queue_status();
    }
    
    /**
     * Check if queue is processing
     */
    public function is_processing() {
        return get_transient('kotacom_ai_queue_processing') !== false;
    }
    
    /**
     * Force stop queue processing
     */
    public function stop_processing() {
        delete_transient('kotacom_ai_queue_processing');
        
        // Reset any items stuck in processing status
        global $wpdb;
        $queue_table = $wpdb->prefix . 'kotacom_queue';
        
        $wpdb->update(
            $queue_table,
            array('status' => 'pending'),
            array('status' => 'processing'),
            array('%s'),
            array('%s')
        );
    }
}
