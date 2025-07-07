<?php
/**
 * Plugin Name: Kotacom AI Content Generator
 * Plugin URI: https://kotacom.com/plugins/ai-content-generator
 * Description: Plugin WordPress untuk generate konten AI dengan manajemen kata kunci, template prompt, dan sistem antrian yang robust.
 * Version: 1.2.0
 * Author: Kotacom
 * Author URI: https://kotacom.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kotacom-ai
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KOTACOM_AI_VERSION', '1.2.0');
define('KOTACOM_AI_PLUGIN_FILE', __FILE__);
define('KOTACOM_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KOTACOM_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KOTACOM_AI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Debug mode
if (!defined('KOTACOM_AI_DEBUG')) {
    define('KOTACOM_AI_DEBUG', false);
}

/**
 * Global variable to hold the current keyword being processed for shortcodes.
 * This helps shortcodes like [ai_content] to know the context during preview/generation.
 */
global $kotacom_ai_current_keyword;
$kotacom_ai_current_keyword = '';


/**
 * Check if Action Scheduler is available
 */
function kotacom_ai_check_action_scheduler() {
    if (!function_exists('as_schedule_single_action')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo __('Kotacom AI: Action Scheduler is recommended for better background processing. Please install WooCommerce or Action Scheduler plugin.', 'kotacom-ai');
            echo '</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Main plugin class
 */
class KotacomAI {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Plugin components
     */
    public $database;
    public $admin;
    public $api_handler;
    public $background_processor;
    public $content_generator;
    public $template_manager;
    public $template_editor; 
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize components
        $this->init_components();
        
        // Setup hooks
        $this->setup_hooks();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-database.php';
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-api-handler.php';
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-background-processor.php';
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-content-generator.php';
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-template-manager.php'; 
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-template-editor.php';
        
        if (is_admin()) {
            require_once KOTACOM_AI_PLUGIN_DIR . 'admin/class-admin.php';
        }
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->database = new KotacomAI_Database();
        $this->api_handler = new KotacomAI_API_Handler();
        $this->background_processor = new KotacomAI_Background_Processor();
        $this->content_generator = new KotacomAI_Content_Generator();
        $this->template_manager = new KotacomAI_Template_Manager(); 
        $this->template_editor = new KotacomAI_Template_Editor();
        
        if (is_admin()) {
            $this->admin = new KotacomAI_Admin();
        }
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Init hook
        add_action('init', array($this, 'init_plugin'));
        
        // Check Action Scheduler
        add_action('admin_init', 'kotacom_ai_check_action_scheduler');
        
        // Admin notices for fallback usage
        add_action('admin_notices', array($this, 'show_fallback_notices'));
        
        // AJAX hooks
        $this->setup_ajax_hooks();
    }
    
    /**
     * Setup AJAX hooks
     */
    private function setup_ajax_hooks() {
        // Keywords management
        add_action('wp_ajax_kotacom_add_keyword', array($this, 'ajax_add_keyword'));
        add_action('wp_ajax_kotacom_add_keywords_bulk', array($this, 'ajax_add_keywords_bulk'));
        add_action('wp_ajax_kotacom_update_keyword', array($this, 'ajax_update_keyword'));
        add_action('wp_ajax_kotacom_delete_keyword', array($this, 'ajax_delete_keyword'));
        add_action('wp_ajax_kotacom_get_keywords', array($this, 'ajax_get_keywords'));
        
        // Prompts management
        add_action('wp_ajax_kotacom_add_prompt', array($this, 'ajax_add_prompt'));
        add_action('wp_ajax_kotacom_update_prompt', array($this, 'ajax_update_prompt'));
        add_action('wp_ajax_kotacom_delete_prompt', array($this, 'ajax_delete_prompt'));
        add_action('wp_ajax_kotacom_get_prompts', array($this, 'ajax_get_prompts'));
        
        // Content generation
        add_action('wp_ajax_kotacom_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_kotacom_get_queue_status', array($this, 'ajax_get_queue_status'));
        add_action('wp_ajax_kotacom_get_processing_status', array($this, 'ajax_get_processing_status'));
        add_action('wp_ajax_kotacom_retry_failed', array($this, 'ajax_retry_failed'));
        add_action('wp_ajax_kotacom_test_api', array($this, 'ajax_test_api'));
        
        // Provider management
        add_action('wp_ajax_kotacom_check_provider_status', array($this, 'ajax_check_provider_status'));
        add_action('wp_ajax_kotacom_test_provider_connection', array($this, 'ajax_test_provider_connection'));

        // Template management (from Template Manager)
        add_action('wp_ajax_kotacom_save_template', array($this->template_manager, 'ajax_save_template'));
        add_action('wp_ajax_kotacom_preview_template', array($this->template_manager, 'ajax_preview_template'));
        add_action('wp_ajax_kotacom_duplicate_template', array($this->template_manager, 'ajax_duplicate_template'));
        add_action('wp_ajax_kotacom_get_template', array($this->template_manager, 'ajax_get_template')); // New
        add_action('wp_ajax_kotacom_get_templates', array($this->template_manager, 'ajax_get_templates')); // New

        // AJAX handler: Generate post from template and keyword
        add_action('wp_ajax_kotacom_generate_post', function() {
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(['message' => 'Permission denied']);
            }
            $template_content = $_POST['template_content'] ?? '';
            $keyword = $_POST['keyword'] ?? '';
            if (!$template_content || !$keyword) {
                wp_send_json_error(['message' => 'Missing data']);
            }
            // Replace {keyword} in template
            $content = str_replace('{keyword}', sanitize_text_field($keyword), $template_content);
            $post_id = wp_insert_post([
                'post_title' => $keyword,
                'post_content' => $content,
                'post_status' => 'draft',
                'post_type' => 'post'
            ]);
            if ($post_id) {
                wp_send_json_success(['edit_link' => get_edit_post_link($post_id)]);
            } else {
                wp_send_json_error(['message' => 'Failed to create post']);
            }
        });
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->database->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules (important for custom post types like templates)
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cancel all scheduled actions
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('kotacom_ai_process_batch', array(), 'kotacom-ai');
            as_unschedule_all_actions('kotacom_ai_process_single_item', array(), 'kotacom-ai');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin after WordPress is loaded
     */
    public function init_plugin() {
        // Load text domain
        load_plugin_textdomain('kotacom-ai', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Show fallback notices
     */
    public function show_fallback_notices() {
        $fallback_notice = get_transient('kotacom_ai_fallback_notice');
        
        if ($fallback_notice) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('Kotacom AI:', 'kotacom-ai') . '</strong> ';
            echo sprintf(
                __('Primary provider %s failed, automatically switched to %s for content generation.', 'kotacom-ai'),
                '<code>' . esc_html($fallback_notice['original_provider']) . '</code>',
                '<code>' . esc_html($fallback_notice['fallback_provider']) . '</code>'
            );
            echo ' <a href="' . admin_url('admin.php?page=kotacom-ai-settings') . '">' . __('Check settings', 'kotacom-ai') . '</a>';
            echo '</p></div>';
            
            // Delete the transient so it only shows once
            delete_transient('kotacom_ai_fallback_notice');
        }
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'api_provider' => 'google_ai',
            'google_ai_model' => 'gemini-1.5-flash',
            'openai_model' => 'gpt-4o-mini',
            'groq_model' => 'llama-3.3-70b-versatile',
            'cohere_model' => 'command',
            'huggingface_model' => 'microsoft/DialoGPT-large',
            'together_model' => 'meta-llama/Llama-3.2-3B-Instruct-Turbo',
            'anthropic_model' => 'claude-3-5-sonnet-20241022',
            'replicate_model' => 'meta/llama-2-7b-chat',
            'openrouter_model' => 'mistralai/mistral-7b-instruct', 
            'perplexity_model' => 'llama-3-sonar-small-32k-online', 
            'default_tone' => 'informative',
            'default_length' => '500',
            'default_audience' => 'general',
            'default_post_type' => 'post',
            'default_post_status' => 'draft',
            'queue_batch_size' => 5
        );
        
        foreach ($defaults as $key => $value) {
            if (!get_option('kotacom_ai_' . $key)) {
                update_option('kotacom_ai_' . $key, $value);
            }
        }
    }
    
    // AJAX Handlers for Keywords (unchanged)
    public function ajax_add_keyword() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        $tags = sanitize_text_field($_POST['tags'] ?? '');
        
        if (empty($keyword)) {
            wp_send_json_error(array('message' => __('Keyword is required', 'kotacom-ai')));
        }
        
        $result = $this->database->add_keyword($keyword, $tags);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Keyword added successfully', 'kotacom-ai')));
        } else {
            wp_send_json_error(array('message' => __('Failed to add keyword', 'kotacom-ai')));
        }
    }
    
    public function ajax_add_keywords_bulk() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $keywords = sanitize_textarea_field($_POST['keywords'] ?? '');
        $tags = sanitize_text_field($_POST['tags'] ?? '');
        
        if (empty($keywords)) {
            wp_send_json_error(array('message' => __('Keywords are required', 'kotacom-ai')));
        }
        
        $keywords_array = array_filter(array_map('trim', explode("\n", $keywords)));
        
        $success_count = 0;
        foreach ($keywords_array as $keyword) {
            if ($this->database->add_keyword($keyword, $tags)) {
                $success_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d keywords added successfully', 'kotacom-ai'), $success_count)
        ));
    }
    
    public function ajax_update_keyword() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $id = intval($_POST['id'] ?? 0);
        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        $tags = sanitize_text_field($_POST['tags'] ?? '');
        
        if (empty($id) || empty($keyword)) {
            wp_send_json_error(array('message' => __('ID and keyword are required', 'kotacom-ai')));
        }
        
        $result = $this->database->update_keyword($id, $keyword, $tags);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Keyword updated successfully', 'kotacom-ai')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update keyword', 'kotacom-ai')));
        }
    }
    
    public function ajax_delete_keyword() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if (empty($id)) {
            wp_send_json_error(array('message' => __('ID is required', 'kotacom-ai')));
        }
        
        $result = $this->database->delete_keyword($id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Keyword deleted successfully', 'kotacom-ai')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete keyword', 'kotacom-ai')));
        }
    }
    
    public function ajax_get_keywords() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $search = sanitize_text_field($_POST['search'] ?? '');
        $tag_filter = sanitize_text_field($_POST['tag_filter'] ?? '');
        
        $keywords = $this->database->get_keywords($page, $per_page, $search, $tag_filter);
        $total = $this->database->get_keywords_count($search, $tag_filter);
        
        wp_send_json_success(array(
            'keywords' => $keywords,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        ));
    }
    
    // AJAX Handlers for Prompts (unchanged)
    public function ajax_add_prompt() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $prompt_name = sanitize_text_field($_POST['prompt_name'] ?? '');
        $prompt_template = sanitize_textarea_field($_POST['prompt_template'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        if (empty($prompt_name) || empty($prompt_template)) {
            wp_send_json_error(array('message' => __('Prompt name and template are required', 'kotacom-ai')));
        }
        
        $result = $this->database->add_prompt($prompt_name, $prompt_template, $description);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Prompt template added successfully', 'kotacom-ai')));
        } else {
            wp_send_json_error(array('message' => __('Failed to add prompt template', 'kotacom-ai')));
        }
    }
    
    public function ajax_update_prompt() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $id = intval($_POST['id'] ?? 0);
        $prompt_name = sanitize_text_field($_POST['prompt_name'] ?? '');
        $prompt_template = sanitize_textarea_field($_POST['prompt_template'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        if (empty($id) || empty($prompt_name) || empty($prompt_template)) {
            wp_send_json_error(array('message' => __('ID, prompt name and template are required', 'kotacom-ai')));
        }
        
        $result = $this->database->update_prompt($id, $prompt_name, $prompt_template, $description);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Prompt template updated successfully', 'kotacom-ai')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update prompt template', 'kotacom-ai')));
        }
    }
    
    public function ajax_delete_prompt() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if (empty($id)) {
            wp_send_json_error(array('message' => __('ID is required', 'kotacom-ai')));
        }
        
        $result = $this->database->delete_prompt($id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Prompt template deleted successfully', 'kotacom-ai')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete prompt template', 'kotacom-ai')));
        }
    }
    
    public function ajax_get_prompts() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $prompts = $this->database->get_prompts();
        
        wp_send_json_success(array('prompts' => $prompts));
    }
    
    // AJAX Handlers for Content Generation - Enhanced
    public function ajax_generate_content() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $keywords = isset($_POST['keywords']) && is_array($_POST['keywords']) ? array_map('sanitize_text_field', $_POST['keywords']) : array();
        $prompt_template = sanitize_textarea_field($_POST['prompt_template'] ?? '');
        
        if (empty($keywords) || empty($prompt_template)) {
            wp_send_json_error(array('message' => __('Keywords and prompt template are required', 'kotacom-ai')));
        }
        
        $parameters = array(
            'tone' => sanitize_text_field($_POST['tone'] ?? 'informative'),
            'length' => sanitize_text_field($_POST['length'] ?? '500'),
            'audience' => sanitize_text_field($_POST['audience'] ?? 'general'),
            'niche' => sanitize_text_field($_POST['niche'] ?? '')
        );
        
        $categories = isset($_POST['categories']) && is_array($_POST['categories']) ? array_map('intval', $_POST['categories']) : array();
        
        $post_settings = array(
            'post_type' => sanitize_text_field($_POST['post_type'] ?? 'post'),
            'post_status' => sanitize_text_field($_POST['post_status'] ?? 'draft'),
            'categories' => $categories,
            'tags' => sanitize_text_field($_POST['tags'] ?? '')
        );
        
        // Handle provider override
        $provider_override = array();
        if (!empty($_POST['session_provider'])) {
            $provider_override['provider'] = sanitize_text_field($_POST['session_provider']);
            
            if (!empty($_POST['session_model'])) {
                $provider_override['model'] = sanitize_text_field($_POST['session_model']);
            }
        }
        
        $result = $this->content_generator->generate_content($keywords, $prompt_template, $parameters, $post_settings, $provider_override);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function ajax_get_queue_status() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $status = $this->background_processor->get_queue_stats();
        
        wp_send_json_success($status);
    }
    
    public function ajax_get_processing_status() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $batch_id = sanitize_text_field($_POST['batch_id'] ?? '');
        
        if (empty($batch_id)) {
            wp_send_json_error(array('message' => __('Batch ID required', 'kotacom-ai')));
        }
        
        $status = $this->content_generator->get_batch_status($batch_id);
        
        wp_send_json_success($status);
    }
    
    public function ajax_retry_failed() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $batch_id = sanitize_text_field($_POST['batch_id'] ?? '');
        
        $result = $this->background_processor->retry_failed_items($batch_id);
        
        if ($result > 0) {
            wp_send_json_success(array('message' => sprintf(__('%d failed items queued for retry', 'kotacom-ai'), $result)));
        } else {
            wp_send_json_error(array('message' => __('No failed items to retry', 'kotacom-ai')));
        }
    }
    
    public function ajax_test_api() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($provider) || empty($api_key)) {
            wp_send_json_error(array('message' => __('Provider and API key are required', 'kotacom-ai')));
        }
        
        $result = $this->api_handler->test_api_connection($provider, $api_key);
        
        if ($result['success']) {
            wp_send_json_success(array('message' => __('API connection successful', 'kotacom-ai')));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }
    
    // NEW AJAX Handlers for Provider Management
    public function ajax_check_provider_status() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        
        if (empty($provider)) {
            wp_send_json_error(array('message' => __('Provider is required', 'kotacom-ai')));
        }
        
        $status = $this->content_generator->check_provider_status($provider);
        
        wp_send_json_success($status);
    }
    
    public function ajax_test_provider_connection() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        
        if (empty($provider)) {
            wp_send_json_error(array('message' => __('Provider is required', 'kotacom-ai')));
        }
        
        $api_key = get_option('kotacom_ai_' . $provider . '_api_key');
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => __('API key not configured for this provider', 'kotacom-ai')));
        }
        
        $result = $this->api_handler->test_api_connection($provider, $api_key);
        
        if ($result['success']) {
            wp_send_json_success(array('message' => __('Connection successful', 'kotacom-ai')));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }
}

/**
 * Initialize the plugin
 */
function kotacom_ai() {
    return KotacomAI::get_instance();
}

// Start the plugin
kotacom_ai();
