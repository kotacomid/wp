<?php
/**
 * Content generator class - Enhanced with Provider Selection Support
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Content_Generator {
    
    private $database;
    private $api_handler;
    private $background_processor;
    
    public function __construct() {
        $this->database = new KotacomAI_Database();
        $this->api_handler = new KotacomAI_API_Handler();
        $this->background_processor = new KotacomAI_Background_Processor();
    }
    
    /**
     * Generate content for keywords with provider selection support
     */
    public function generate_content($keywords, $prompt_template, $parameters, $post_settings, $provider_override = null) {
        // Validate inputs
        if (empty($keywords) || empty($prompt_template)) {
            return array(
                'success' => false,
                'message' => __('Keywords and prompt template are required', 'kotacom-ai')
            );
        }
        
        // Handle provider override
        $original_provider = null;
        $original_model = null; // Initialize original_model
        if (!empty($provider_override['provider'])) {
            $original_provider = get_option('kotacom_ai_api_provider');
            update_option('kotacom_ai_api_provider', $provider_override['provider']);
            
            // Also set model if provided
            if (!empty($provider_override['model'])) {
                $model_option = 'kotacom_ai_' . $provider_override['provider'] . '_model';
                $original_model = get_option($model_option);
                update_option($model_option, $provider_override['model']);
            }
        }
        
        // Fire before generation hook
        do_action('kotacom_ai_before_content_generation', $keywords, $prompt_template, $parameters, $post_settings, $provider_override);
        
        try {
            if (count($keywords) === 1) {
                // Single keyword - process immediately
                $result = $this->process_single_keyword($keywords[0], $prompt_template, $parameters, $post_settings);
                
                $response = array(
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'provider_used' => $provider_override['provider'] ?? get_option('kotacom_ai_api_provider'),
                    'results' => array(
                        array(
                            'keyword' => $keywords[0],
                            'status' => $result['success'] ? 'completed' : 'error',
                            'message' => $result['message'],
                            'post_id' => $result['post_id'] ?? null
                        )
                    )
                );
            } else {
                // Multiple keywords - use background processor
                $response = $this->background_processor->add_to_queue($keywords, $prompt_template, $parameters, $post_settings);
                $response['provider_used'] = $provider_override['provider'] ?? get_option('kotacom_ai_api_provider');
            }
            
        } catch (Exception $e) {
            $response = array(
                'success' => false,
                'message' => __('Generation failed: ', 'kotacom-ai') . $e->getMessage()
            );
        } finally {
            // Restore original provider settings
            if ($original_provider !== null) {
                update_option('kotacom_ai_api_provider', $original_provider);
                
                if (!empty($provider_override['model']) && isset($original_model)) {
                    $model_option = 'kotacom_ai_' . $provider_override['provider'] . '_model';
                    update_option($model_option, $original_model);
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Process single keyword immediately with enhanced error handling
     */
    private function process_single_keyword($keyword, $prompt_template, $parameters, $post_settings) {
        // Replace {keyword} in prompt template
        $prompt = str_replace('{keyword}', $keyword, $prompt_template);
        
        // Apply filters to prompt
        $prompt = apply_filters('kotacom_ai_prompt_template', $prompt, $keyword, $parameters);
        
        // Handle unlimited length
        if (isset($parameters['length']) && $parameters['length'] === 'unlimited') {
            // Remove length restrictions for unlimited mode
            unset($parameters['length']);
            $parameters['unlimited'] = true;
        }
        
        // Generate content using AI API with fallback support
        $api_result = $this->generate_with_fallback($prompt, $parameters);
        
        if (!$api_result['success']) {
            return array(
                'success' => false,
                'message' => $api_result['error']
            );
        }
        
        // Apply filters to generated content
        $content = apply_filters('kotacom_ai_generated_content', $api_result['content'], $keyword, $parameters);
        
        // Create WordPress post
        $post_id = $this->create_wordpress_post($keyword, $content, $post_settings);
        
        if ($post_id) {
            return array(
                'success' => true,
                'message' => sprintf(__('Content generated and post created (ID: %d)', 'kotacom-ai'), $post_id),
                'post_id' => $post_id
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Content generated but failed to create post', 'kotacom-ai')
            );
        }
    }
    
    /**
     * Generate content with automatic fallback to alternative providers
     */
    private function generate_with_fallback($prompt, $parameters) {
        $current_provider = get_option('kotacom_ai_api_provider');
        $providers = $this->api_handler->get_providers();
        
        // Try current provider first
        $result = $this->api_handler->generate_content($prompt, $parameters);
        
        if ($result['success']) {
            return $result;
        }
        
        // If current provider fails, try fallback providers
        $fallback_providers = $this->get_fallback_providers($current_provider);
        
        foreach ($fallback_providers as $fallback_provider) {
            // Check if fallback provider is configured
            $api_key = get_option('kotacom_ai_' . $fallback_provider . '_api_key');
            if (empty($api_key)) {
                continue;
            }
            
            // Temporarily switch to fallback provider
            update_option('kotacom_ai_api_provider', $fallback_provider);
            
            $fallback_result = $this->api_handler->generate_content($prompt, $parameters);
            
            if ($fallback_result['success']) {
                // Log successful fallback
                $this->log_fallback_success($current_provider, $fallback_provider);
                
                // Restore original provider
                update_option('kotacom_ai_api_provider', $current_provider);
                
                return array(
                    'success' => true,
                    'content' => $fallback_result['content'],
                    'fallback_used' => $fallback_provider
                );
            }
        }
        
        // Restore original provider
        update_option('kotacom_ai_api_provider', $current_provider);
        
        // All providers failed
        return array(
            'success' => false,
            'error' => sprintf(__('All providers failed. Last error: %s', 'kotacom-ai'), $result['error'])
        );
    }
    
    /**
     * Get fallback providers based on current provider
     */
    private function get_fallback_providers($current_provider) {
        // Define fallback hierarchy based on reliability and free tiers
        $fallback_hierarchy = array(
            'google_ai' => array('groq', 'cohere', 'huggingface', 'together', 'openrouter', 'perplexity'),
            'groq' => array('google_ai', 'cohere', 'together', 'huggingface', 'openrouter', 'perplexity'),
            'openai' => array('anthropic', 'google_ai', 'groq', 'openrouter', 'perplexity'),
            'anthropic' => array('openai', 'google_ai', 'groq', 'openrouter', 'perplexity'),
            'cohere' => array('google_ai', 'groq', 'huggingface', 'openrouter', 'perplexity'),
            'huggingface' => array('google_ai', 'groq', 'cohere', 'openrouter', 'perplexity'),
            'together' => array('google_ai', 'groq', 'cohere', 'openrouter', 'perplexity'),
            'replicate' => array('google_ai', 'groq', 'together', 'openrouter', 'perplexity'),
            'openrouter' => array('google_ai', 'groq', 'openai', 'anthropic', 'perplexity'), // Prioritize other paid/reliable ones
            'perplexity' => array('google_ai', 'groq', 'openai', 'anthropic', 'openrouter') // Prioritize other paid/reliable ones
        );
        
        return $fallback_hierarchy[$current_provider] ?? array('google_ai', 'groq', 'openrouter', 'perplexity');
    }
    
    /**
     * Log successful fallback for analytics
     */
    private function log_fallback_success($original_provider, $fallback_provider) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'original_provider' => $original_provider,
            'fallback_provider' => $fallback_provider,
            'user_id' => get_current_user_id()
        );
        
        // Store in transient for admin notice
        set_transient('kotacom_ai_fallback_notice', $log_entry, 300); // 5 minutes
        
        // Log for debugging if enabled
        if (defined('KOTACOM_AI_DEBUG') && KOTACOM_AI_DEBUG) {
                error_log('Kotacom AI: Fallback successful - ' . $original_provider . ' -> ' . $fallback_provider);
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
                'kotacom_ai_generated_at' => current_time('mysql'),
                'kotacom_ai_provider_used' => get_option('kotacom_ai_api_provider')
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
     * Get batch processing status
     */
    public function get_batch_status($batch_id) {
        return $this->background_processor->get_batch_status($batch_id);
    }
    
    /**
     * Check provider status and configuration
     */
    public function check_provider_status($provider) {
        $api_key = get_option('kotacom_ai_' . $provider . '_api_key');
        
        if (empty($api_key)) {
            return array(
                'configured' => false,
                'status' => 'not_configured',
                'message' => __('API key not configured', 'kotacom-ai')
            );
        }
        
        // Test connection
        $test_result = $this->api_handler->test_api_connection($provider, $api_key);
        
        return array(
            'configured' => true,
            'status' => $test_result['success'] ? 'connected' : 'error',
            'message' => $test_result['success'] ? __('Ready to use', 'kotacom-ai') : $test_result['error']
        );
    }
    
    /**
     * Get content generation statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total AI generated posts
        $stats['total_generated'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'kotacom_ai_generated' AND meta_value = '1'"
        );
        
        // Generated posts by provider
        $stats['by_provider'] = $wpdb->get_results(
            "SELECT pm.meta_value as provider, COUNT(*) as count 
             FROM {$wpdb->postmeta} pm1
             INNER JOIN {$wpdb->postmeta} pm ON pm1.post_id = pm.post_id 
             WHERE pm1.meta_key = 'kotacom_ai_generated' AND pm1.meta_value = '1'
             AND pm.meta_key = 'kotacom_ai_provider_used'
             GROUP BY pm.meta_value",
            ARRAY_A
        );
        
        // Generated posts by status
        $stats['by_status'] = $wpdb->get_results(
            "SELECT p.post_status, COUNT(*) as count 
             FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE pm.meta_key = 'kotacom_ai_generated' AND pm.meta_value = '1' 
             GROUP BY p.post_status",
            ARRAY_A
        );
        
        // Generated posts by date (last 30 days)
        $stats['by_date'] = $wpdb->get_results(
            "SELECT DATE(p.post_date) as date, COUNT(*) as count 
             FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE pm.meta_key = 'kotacom_ai_generated' AND pm.meta_value = '1' 
             AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(p.post_date) 
             ORDER BY date DESC",
            ARRAY_A
        );
        
        // Queue statistics
        $stats['queue'] = $this->background_processor->get_queue_stats();
        
        return $stats;
    }
}
