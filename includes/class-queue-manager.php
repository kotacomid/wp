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
        
        $this->setup_hooks();
    }
    
    /**
     * Initialize queue manager
     */
    public function init() {
        $this->setup_hooks();
    }
    
    /**
     * Setup hooks and schedules
     */
    private function setup_hooks() {
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
    public function add_to_queue($keywords_or_action, $prompt_template = null, $parameters = null, $post_settings = null, $priority = 10, $delay = 0) {
        // New signature: add_to_queue($action, $data, $priority, $delay)
        if ($prompt_template === null && is_string($keywords_or_action)) {
            // This is the new queue manager signature where:
            // $keywords_or_action = action, $parameters = data, $post_settings = priority, $priority = delay
            return $this->add_single_item_to_queue($keywords_or_action, $parameters, $post_settings ?: 10, $priority ?: 0);
        }
        
        // Old signature: add_to_queue($keywords, $prompt_template, $parameters, $post_settings) 
        $keywords = $keywords_or_action;
        $batch_id = uniqid('batch_', true);
        
        if (!is_array($keywords)) {
            $keywords = array($keywords);
        }
        
        $success_count = 0;
        foreach ($keywords as $keyword) {
            $queue_item_id = $this->add_single_item_to_queue('generate_content', array(
                'keyword' => $keyword,
                'prompt' => $prompt_template,
                'params' => $parameters,
                'create_post' => true,
                'post_status' => $post_settings['post_status'] ?? 'draft',
                'post_type' => $post_settings['post_type'] ?? 'post',
                'categories' => $post_settings['categories'] ?? array(),
                'tags' => $post_settings['tags'] ?? '',
                'batch_id' => $batch_id
            ), $priority, $delay);
            
            if ($queue_item_id) {
                $success_count++;
            }
        }
        
        // Store batch info
        $batches = get_option('kotacom_ai_batches', array());
        $batches[$batch_id] = array(
            'id' => $batch_id,
            'created_at' => current_time('mysql'),
            'total_items' => count($keywords),
            'keywords' => $keywords,
            'status' => 'processing'
        );
        update_option('kotacom_ai_batches', $batches);
        
        return array(
            'success' => $success_count > 0,
            'message' => sprintf(__('%d keywords added to processing queue', 'kotacom-ai'), $success_count),
            'batch_id' => $batch_id,
            'queued_items' => $success_count
        );
    }
    
    /**
     * Add single item to queue (internal method)
     */
    public function add_single_item_to_queue($action, $data, $priority = 10, $delay = 0) {
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
        
                    if (class_exists('KotacomAI_Logger')) {
                KotacomAI_Logger::add('queue_add', 1, null, "Added item to queue: {$action}");
            }
        
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
                    if (class_exists('KotacomAI_Logger')) {
                    KotacomAI_Logger::add('queue_process', 1, $item['data']['post_id'] ?? null, "Completed: {$item['action']}");
                }
                } else {
                    throw new Exception('Processing returned false');
                }
                
            } catch (Exception $e) {
                $queue[$key]['attempts']++;
                $queue[$key]['last_error'] = $e->getMessage();
                
                if ($queue[$key]['attempts'] >= $this->max_attempts) {
                    $queue[$key]['status'] = 'failed';
                    $queue[$key]['failed_at'] = current_time('mysql');
                    if (class_exists('KotacomAI_Logger')) {
                    KotacomAI_Logger::add('queue_failed', 0, $item['data']['post_id'] ?? null, "Failed after {$this->max_attempts} attempts: {$item['action']} - {$e->getMessage()}");
                }
                } else {
                    $queue[$key]['status'] = 'retry';
                    // Exponential backoff: wait 2^attempts minutes
                    $delay = pow(2, $queue[$key]['attempts']) * 60;
                    $queue[$key]['scheduled_at'] = date('Y-m-d H:i:s', current_time('timestamp') + $delay);
                    if (class_exists('KotacomAI_Logger')) {
                        KotacomAI_Logger::add('queue_retry', 0, $item['data']['post_id'] ?? null, "Retry #{$queue[$key]['attempts']}: {$item['action']} - {$e->getMessage()}");
                    }
                }
            }
            
            $processed++;
            $updated = true;
        }
        
        if ($updated) {
            update_option($this->queue_option, $queue);
        }
        
        if ($processed > 0) {
            if (class_exists('KotacomAI_Logger')) {
                KotacomAI_Logger::add('queue_batch', 1, null, "Processed {$processed} items");
            }
        }
        
        // Update last process time
        update_option('kotacom_ai_last_queue_process', current_time('mysql'));
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
        try {
            // Extract data
            $keyword = $data['keyword'] ?? '';
            $prompt = $data['prompt'] ?? '';
            $params = $data['params'] ?? array();
            
            if (empty($keyword) || empty($prompt)) {
                throw new Exception('Missing keyword or prompt in queue data');
            }
            
            // Replace {keyword} in prompt
            $final_prompt = str_replace('{keyword}', $keyword, $prompt);
            
            // Use API handler directly for queue processing
            $api_handler = new KotacomAI_API_Handler();
            $result = $api_handler->generate_content($final_prompt, $params);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Content generation failed');
            }
            
            $generated_content = $result['content'];
            
            // Create post if specified
            if (isset($data['create_post']) && $data['create_post']) {
                // Generate title from keyword or content
                $title = $this->generate_post_title($keyword, $generated_content);
                
                $post_data = array(
                    'post_title' => $title,
                    'post_content' => $generated_content,
                    'post_status' => $data['post_status'] ?? 'draft',
                    'post_type' => $data['post_type'] ?? 'post',
                    'post_author' => get_current_user_id() ?: 1,
                    'meta_input' => array(
                        'kotacom_ai_generated' => true,
                        'kotacom_ai_keyword' => $keyword,
                        'kotacom_ai_generated_at' => current_time('mysql'),
                        'kotacom_ai_provider_used' => get_option('kotacom_ai_api_provider'),
                        'kotacom_ai_batch_id' => $data['batch_id'] ?? ''
                    )
                );
                
                // Add categories
                if (!empty($data['categories'])) {
                    $post_data['post_category'] = array_map('intval', $data['categories']);
                }
                
                $post_id = wp_insert_post($post_data);
                
                if ($post_id && !is_wp_error($post_id)) {
                    // Add tags
                    if (!empty($data['tags'])) {
                        wp_set_post_tags($post_id, explode(',', $data['tags']));
                    }
                    
                    // Generate featured image if enabled
                    if (get_option('kotacom_ai_auto_featured_image', false)) {
                        $this->add_single_item_to_queue('generate_image', array(
                            'post_id' => $post_id,
                            'prompt' => "Featured image for: " . $keyword,
                            'featured' => true
                        ), 5);
                    }
                    
                    // Log successful creation
                    if (class_exists('KotacomAI_Logger')) {
                        KotacomAI_Logger::add('queue_generate', 1, $post_id, "Generated content for: {$keyword}");
                    }
                    
                    return $post_id;
                } else {
                    throw new Exception('Failed to create WordPress post');
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            if (class_exists('KotacomAI_Logger')) {
                KotacomAI_Logger::add('queue_generate_error', 0, null, "Failed to generate content for {$keyword}: " . $e->getMessage());
            }
            throw $e;
        }
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
        
        // Replace placeholders in prompt
        $prompt = str_replace(
            array('{current_content}', '{title}', '{published_date}'),
            array(wp_strip_all_tags($post->post_content), $post->post_title, get_the_date('', $post)),
            $refresh_prompt
        );
        
        // Use API handler directly for refresh content
        $api_handler = new KotacomAI_API_Handler();
        $result = $api_handler->generate_content($prompt, array('tone' => 'informative', 'length' => 'unlimited'));
        
        if ($result['success']) {
            // Save new revision so editor can compare
            wp_save_post_revision($post_id);
            
            $update_data = array(
                'ID' => $post_id,
                'post_content' => $result['content']
            );
            
            if (isset($data['update_date']) && $data['update_date']) {
                $update_data['post_date'] = current_time('mysql');
            }
            
            $updated = wp_update_post($update_data);
            
            if ($updated && !is_wp_error($updated)) {
                if (class_exists('KotacomAI_Logger')) {
                KotacomAI_Logger::add('refresh', 1, $post_id, 'Queue refresh successful');
            }
                return true;
            } else {
                throw new Exception('Failed to update post content');
            }
        } else {
            throw new Exception($result['error'] ?? 'Failed to generate refresh content');
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
                
                if (class_exists('KotacomAI_Logger')) {
                KotacomAI_Logger::add('queue_retry_manual', 1, null, "Manually retried: {$item['action']}");
            }
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
            if (class_exists('KotacomAI_Logger')) {
            KotacomAI_Logger::add('queue_cleanup', 1, null, "Cleaned {$cleaned} old queue items");
        }
        }
    }
    
    /**
     * Clear all queue items
     */
    public function clear_all_queue() {
        delete_option($this->queue_option);
        if (class_exists('KotacomAI_Logger')) {
            KotacomAI_Logger::add('queue_clear', 1, null, "Cleared all queue items");
        }
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

    /**
     * Start batch processing (compatibility method)
     */
    public function start_batch_processing($batch_id) {
        // The queue automatically processes items, so this is mainly for logging
        if (class_exists('KotacomAI_Logger')) {
            KotacomAI_Logger::add('batch_start', 1, null, "Started batch processing: {$batch_id}");
        }
        
        // Update batch status
        $batches = get_option('kotacom_ai_batches', array());
        if (isset($batches[$batch_id])) {
            $batches[$batch_id]['status'] = 'processing';
            $batches[$batch_id]['started_at'] = current_time('mysql');
            update_option('kotacom_ai_batches', $batches);
        }
        
        return true;
    }

    /**
     * Get batch status (compatibility method)
     */
    public function get_batch_status($batch_id) {
        $batches = get_option('kotacom_ai_batches', array());
        
        if (!isset($batches[$batch_id])) {
            return array(
                'success' => false,
                'message' => __('Batch not found', 'kotacom-ai')
            );
        }
        
        $batch = $batches[$batch_id];
        $queue = get_option($this->queue_option, array());
        
        // Count items in this batch
        $batch_items = array_filter($queue, function($item) use ($batch_id) {
            return isset($item['data']['batch_id']) && $item['data']['batch_id'] === $batch_id;
        });
        
        $status_counts = array(
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'retry' => 0
        );
        
        foreach ($batch_items as $item) {
            if (isset($status_counts[$item['status']])) {
                $status_counts[$item['status']]++;
            }
        }
        
        $total_items = count($batch_items);
        $completed_items = $status_counts['completed'];
        $failed_items = $status_counts['failed'];
        $processing_items = $status_counts['processing'] + $status_counts['retry'] + $status_counts['pending'];
        
        return array(
            'success' => true,
            'batch_id' => $batch_id,
            'status' => $batch['status'],
            'total_items' => $total_items,
            'completed_items' => $completed_items,
            'failed_items' => $failed_items,
            'processing_items' => $processing_items,
            'progress_percentage' => $total_items > 0 ? round(($completed_items / $total_items) * 100, 2) : 0,
            'created_at' => $batch['created_at'],
            'started_at' => $batch['started_at'] ?? null
        );
    }

    /**
     * Get queue statistics (compatibility method)
     */
    public function get_queue_stats() {
        $status = $this->get_queue_status();
        
        return array(
            'total_items' => $status['total'],
            'pending_items' => $status['pending'],
            'processing_items' => $status['processing'],
            'completed_items' => $status['completed'],
            'failed_items' => $status['failed'],
            'retry_items' => $status['retry'],
            'last_processed' => get_option('kotacom_ai_last_queue_process', null),
            'is_paused' => $this->is_paused()
        );
    }
}