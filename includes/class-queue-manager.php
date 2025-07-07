<?php
/**
 * Lightweight Queue Manager for Kotacom AI Plugin
 * Replaces WooCommerce Action Scheduler dependency
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Queue_Manager {
    
    private $queue_option = 'kotacom_ai_queue';
    private $settings_option = 'kotacom_ai_queue_settings';
    private $batch_size;
    private $max_attempts;
    
    public function __construct() {
        $this->batch_size = get_option('kotacom_ai_queue_batch_size', 5);
        $this->max_attempts = 3;
        
        $this->init();
    }
    
    /**
     * Initialize queue manager
     */
    private function init() {
        // Schedule queue processing
        add_action('kotacom_ai_process_queue', array($this, 'process_queue'));
        add_action('kotacom_ai_cleanup_queue', array($this, 'cleanup_queue'));
        
        // Schedule events if not already scheduled
        if (!wp_next_scheduled('kotacom_ai_process_queue')) {
            $interval = get_option('kotacom_ai_queue_processing_interval', 'every_minute');
            wp_schedule_event(time(), $interval, 'kotacom_ai_process_queue');
        }
        
        if (!wp_next_scheduled('kotacom_ai_cleanup_queue')) {
            wp_schedule_event(time(), 'daily', 'kotacom_ai_cleanup_queue');
        }
        
        // Add custom cron intervals
        add_filter('cron_schedules', array($this, 'add_cron_intervals'));
        
        // Admin hooks
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_cron_intervals($schedules) {
        $schedules['every_minute'] = array(
            'interval' => 60,
            'display' => __('Every Minute', 'kotacom-ai')
        );
        
        $schedules['every_five_minutes'] = array(
            'interval' => 300,
            'display' => __('Every 5 Minutes', 'kotacom-ai')
        );
        
        return $schedules;
    }
    
    /**
     * Add item to queue
     */
    public function add_to_queue($action, $data, $priority = 10, $delay = 0) {
        $queue = get_option($this->queue_option, array());
        
        $item = array(
            'id' => uniqid('ai_', true),
            'action' => $action,
            'data' => $data,
            'status' => 'pending',
            'priority' => $priority,
            'created_at' => current_time('mysql'),
            'scheduled_at' => date('Y-m-d H:i:s', current_time('timestamp') + $delay),
            'attempts' => 0,
            'last_error' => null
        );
        
        $queue[] = $item;
        
        // Sort by priority (higher numbers first)
        usort($queue, function($a, $b) {
            if ($a['priority'] == $b['priority']) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            }
            return $b['priority'] - $a['priority'];
        });
        
        update_option($this->queue_option, $queue);
        
        KotacomAI_Logger::log('queue_add', "Added item to queue: {$action}", null, true);
        
        return $item['id'];
    }
    
    /**
     * Process queue batch
     */
    public function process_queue() {
        $queue = get_option($this->queue_option, array());
        $processed = 0;
        $updated = false;
        
        foreach ($queue as $key => $item) {
            // Skip if batch limit reached
            if ($processed >= $this->batch_size) {
                break;
            }
            
            // Skip if not ready to process
            if ($item['status'] !== 'pending' && $item['status'] !== 'retry') {
                continue;
            }
            
            // Skip if scheduled for future
            if (strtotime($item['scheduled_at']) > current_time('timestamp')) {
                continue;
            }
            
            // Process the item
            $queue[$key]['status'] = 'processing';
            $queue[$key]['started_at'] = current_time('mysql');
            update_option($this->queue_option, $queue);
            
            try {
                $result = $this->process_item($item);
                
                if ($result) {
                    $queue[$key]['status'] = 'completed';
                    $queue[$key]['completed_at'] = current_time('mysql');
                    KotacomAI_Logger::log('queue_process', "Completed: {$item['action']}", $item['data']['post_id'] ?? null, true);
                } else {
                    throw new Exception('Processing returned false');
                }
                
            } catch (Exception $e) {
                $queue[$key]['attempts']++;
                $queue[$key]['last_error'] = $e->getMessage();
                
                if ($queue[$key]['attempts'] >= $this->max_attempts) {
                    $queue[$key]['status'] = 'failed';
                    $queue[$key]['failed_at'] = current_time('mysql');
                    KotacomAI_Logger::log('queue_failed', "Failed after {$this->max_attempts} attempts: {$item['action']} - {$e->getMessage()}", $item['data']['post_id'] ?? null, false);
                } else {
                    $queue[$key]['status'] = 'retry';
                    // Exponential backoff: wait 2^attempts minutes
                    $delay = pow(2, $queue[$key]['attempts']) * 60;
                    $queue[$key]['scheduled_at'] = date('Y-m-d H:i:s', current_time('timestamp') + $delay);
                    KotacomAI_Logger::log('queue_retry', "Retry #{$queue[$key]['attempts']}: {$item['action']} - {$e->getMessage()}", $item['data']['post_id'] ?? null, false);
                }
            }
            
            $processed++;
            $updated = true;
        }
        
        if ($updated) {
            update_option($this->queue_option, $queue);
        }
        
        if ($processed > 0) {
            KotacomAI_Logger::log('queue_batch', "Processed {$processed} items", null, true);
        }
    }
    
    /**
     * Process individual queue item
     */
    private function process_item($item) {
        switch ($item['action']) {
            case 'generate_content':
                return $this->process_content_generation($item['data']);
                
            case 'generate_image':
                return $this->process_image_generation($item['data']);
                
            case 'refresh_content':
                return $this->process_content_refresh($item['data']);
                
            case 'bulk_generate':
                return $this->process_bulk_generation($item['data']);
                
            default:
                throw new Exception("Unknown queue action: {$item['action']}");
        }
    }
    
    /**
     * Process content generation
     */
    private function process_content_generation($data) {
        $generator = new KotacomAI_Content_Generator();
        
        $result = $generator->generate_content(
            $data['keyword'],
            $data['prompt'],
            $data['params']
        );
        
        if ($result && !is_wp_error($result)) {
            // Create post if specified
            if (isset($data['create_post']) && $data['create_post']) {
                $post_data = array(
                    'post_title' => $result['title'] ?? $data['keyword'],
                    'post_content' => $result['content'],
                    'post_excerpt' => $result['excerpt'] ?? '',
                    'post_status' => $data['post_status'] ?? 'draft',
                    'post_type' => $data['post_type'] ?? 'post',
                    'post_category' => $data['categories'] ?? array(),
                    'tags_input' => $data['tags'] ?? ''
                );
                
                $post_id = wp_insert_post($post_data);
                
                if ($post_id && !is_wp_error($post_id)) {
                    // Add meta description
                    if (isset($result['meta_description'])) {
                        update_post_meta($post_id, '_yoast_wpseo_metadesc', $result['meta_description']);
                    }
                    
                    // Generate featured image if enabled
                    if (get_option('kotacom_ai_auto_featured_image')) {
                        $this->add_to_queue('generate_image', array(
                            'post_id' => $post_id,
                            'prompt' => "Featured image for: " . $data['keyword'],
                            'featured' => true
                        ), 5);
                    }
                    
                    return $post_id;
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Process image generation
     */
    private function process_image_generation($data) {
        $image_generator = new KotacomAI_Image_Generator();
        
        $result = $image_generator->generate_image(
            $data['prompt'],
            $data['size'] ?? '1200x800',
            $data['provider'] ?? null
        );
        
        if ($result && !is_wp_error($result)) {
            if (isset($data['post_id']) && isset($data['featured']) && $data['featured']) {
                set_post_thumbnail($data['post_id'], $result['attachment_id']);
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Process content refresh
     */
    private function process_content_refresh($data) {
        $post_id = $data['post_id'];
        $refresh_prompt = $data['refresh_prompt'];
        
        $post = get_post($post_id);
        if (!$post) {
            throw new Exception("Post not found: {$post_id}");
        }
        
        $generator = new KotacomAI_Content_Generator();
        
        // Replace placeholders in prompt
        $prompt = str_replace(
            array('{current_content}', '{title}'),
            array($post->post_content, $post->post_title),
            $refresh_prompt
        );
        
        $result = $generator->generate_content(
            $post->post_title,
            $prompt,
            array('tone' => 'informative', 'length' => '500')
        );
        
        if ($result && !is_wp_error($result)) {
            $update_data = array(
                'ID' => $post_id,
                'post_content' => $result['content']
            );
            
            if (isset($data['update_date']) && $data['update_date']) {
                $update_data['post_date'] = current_time('mysql');
            }
            
            $updated = wp_update_post($update_data);
            return $updated && !is_wp_error($updated);
        }
        
        return false;
    }
    
    /**
     * Process bulk generation
     */
    private function process_bulk_generation($data) {
        foreach ($data['keywords'] as $keyword) {
            $this->add_to_queue('generate_content', array(
                'keyword' => $keyword,
                'prompt' => $data['prompt'],
                'params' => $data['params'],
                'create_post' => true,
                'post_status' => $data['post_status'] ?? 'draft',
                'post_type' => $data['post_type'] ?? 'post',
                'categories' => $data['categories'] ?? array(),
                'tags' => $data['tags'] ?? ''
            ), $data['priority'] ?? 10);
        }
        
        return true;
    }
    
    /**
     * Get queue status
     */
    public function get_queue_status() {
        $queue = get_option($this->queue_option, array());
        
        $status = array(
            'total' => count($queue),
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'retry' => 0
        );
        
        foreach ($queue as $item) {
            $status[$item['status']]++;
        }
        
        return $status;
    }
    
    /**
     * Get failed queue items
     */
    public function get_failed_items($limit = 20) {
        $queue = get_option($this->queue_option, array());
        
        $failed = array_filter($queue, function($item) {
            return $item['status'] === 'failed';
        });
        
        // Sort by most recent first
        usort($failed, function($a, $b) {
            return strtotime($b['failed_at']) - strtotime($a['failed_at']);
        });
        
        return array_slice($failed, 0, $limit);
    }
    
    /**
     * Retry failed item
     */
    public function retry_failed_item($item_id) {
        $queue = get_option($this->queue_option, array());
        
        foreach ($queue as $key => $item) {
            if ($item['id'] === $item_id && $item['status'] === 'failed') {
                $queue[$key]['status'] = 'pending';
                $queue[$key]['attempts'] = 0;
                $queue[$key]['last_error'] = null;
                $queue[$key]['scheduled_at'] = current_time('mysql');
                
                update_option($this->queue_option, $queue);
                
                KotacomAI_Logger::log('queue_retry_manual', "Manually retried: {$item['action']}", null, true);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Clear completed items
     */
    public function cleanup_queue() {
        $queue = get_option($this->queue_option, array());
        $original_count = count($queue);
        
        // Keep items from last 7 days
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        $queue = array_filter($queue, function($item) use ($cutoff_date) {
            // Keep pending, processing, and retry items
            if (in_array($item['status'], array('pending', 'processing', 'retry'))) {
                return true;
            }
            
            // Keep recent items
            $item_date = $item['completed_at'] ?? $item['failed_at'] ?? $item['created_at'];
            return $item_date > $cutoff_date;
        });
        
        // Re-index array
        $queue = array_values($queue);
        
        update_option($this->queue_option, $queue);
        
        $cleaned = $original_count - count($queue);
        if ($cleaned > 0) {
            KotacomAI_Logger::log('queue_cleanup', "Cleaned {$cleaned} old queue items", null, true);
        }
    }
    
    /**
     * Clear all queue items
     */
    public function clear_all_queue() {
        delete_option($this->queue_option);
        KotacomAI_Logger::log('queue_clear', "Cleared all queue items", null, true);
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['kotacom_queue_action']) && wp_verify_nonce($_GET['_wpnonce'], 'kotacom_queue_action')) {
            switch ($_GET['kotacom_queue_action']) {
                case 'process_now':
                    $this->process_queue();
                    wp_redirect(add_query_arg('message', 'processed', wp_get_referer()));
                    exit;
                    
                case 'cleanup':
                    $this->cleanup_queue();
                    wp_redirect(add_query_arg('message', 'cleaned', wp_get_referer()));
                    exit;
                    
                case 'clear_all':
                    $this->clear_all_queue();
                    wp_redirect(add_query_arg('message', 'cleared', wp_get_referer()));
                    exit;
                    
                case 'retry_failed':
                    if (isset($_GET['item_id'])) {
                        $this->retry_failed_item($_GET['item_id']);
                        wp_redirect(add_query_arg('message', 'retried', wp_get_referer()));
                        exit;
                    }
                    break;
            }
        }
    }
    
    /**
     * Pause queue processing
     */
    public function pause_queue() {
        wp_clear_scheduled_hook('kotacom_ai_process_queue');
        update_option('kotacom_ai_queue_paused', true);
    }
    
    /**
     * Resume queue processing
     */
    public function resume_queue() {
        delete_option('kotacom_ai_queue_paused');
        if (!wp_next_scheduled('kotacom_ai_process_queue')) {
            $interval = get_option('kotacom_ai_queue_processing_interval', 'every_minute');
            wp_schedule_event(time(), $interval, 'kotacom_ai_process_queue');
        }
    }
    
    /**
     * Check if queue is paused
     */
    public function is_paused() {
        return get_option('kotacom_ai_queue_paused', false);
    }
}