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
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-image-generator.php';
        
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
        
        // Enhanced AJAX handler for generator-post-template page
        add_action('wp_ajax_kotacom_generate_content_enhanced', array($this, 'ajax_generate_content_enhanced'));

        // Image generation
        add_action('wp_ajax_kotacom_generate_image', array($this, 'ajax_generate_image'));
        add_action('wp_ajax_kotacom_refresh_posts', array($this, 'ajax_refresh_posts'));
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
        
        // Register custom post types
        $this->register_post_types();
    }
    
    /**
     * Register custom post types
     */
    private function register_post_types() {
        // Register kotacom_template post type
        register_post_type('kotacom_template', array(
            'labels' => array(
                'name' => __('AI Templates', 'kotacom-ai'),
                'singular_name' => __('AI Template', 'kotacom-ai'),
                'add_new' => __('Add New Template', 'kotacom-ai'),
                'add_new_item' => __('Add New AI Template', 'kotacom-ai'),
                'edit_item' => __('Edit AI Template', 'kotacom-ai'),
                'new_item' => __('New AI Template', 'kotacom-ai'),
                'view_item' => __('View AI Template', 'kotacom-ai'),
                'search_items' => __('Search AI Templates', 'kotacom-ai'),
                'not_found' => __('No AI templates found', 'kotacom-ai'),
                'not_found_in_trash' => __('No AI templates found in trash', 'kotacom-ai'),
            ),
            'public' => true,
            'has_archive' => false,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'kotacom-ai',
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => false,
            'can_export' => true,
            'exclude_from_search' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'hierarchical' => false,
            'rewrite' => array('slug' => 'ai-templates'),
            'query_var' => true,
            'supports' => array('title', 'editor', 'excerpt', 'custom-fields'),
            'menu_icon' => 'dashicons-media-document',
            'description' => __('Templates for AI content generation', 'kotacom-ai')
        ));
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
    
    /**
     * AJAX: Generate AI Image and optionally set as featured image
     * POST params: prompt, size (optional), post_id (optional), featured (yes/no)
     */
    public function ajax_generate_image() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }

        $prompt   = sanitize_text_field($_POST['prompt'] ?? '');
        $size     = sanitize_text_field($_POST['size'] ?? '1024x1024');
        $post_id  = intval($_POST['post_id'] ?? 0);
        $featured = sanitize_text_field($_POST['featured'] ?? 'no') === 'yes';
        $provider = sanitize_text_field($_POST['provider'] ?? 'openai');

        if (empty($prompt)) {
            wp_send_json_error(array('message' => __('Prompt is required', 'kotacom-ai')));
        }

        $img_gen  = new KotacomAI_Image_Generator();
        $result   = $img_gen->generate_image($prompt, $size, true, $provider);

        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['error']));
        }

        // Sideload image into media library
        if (!function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $attachment_id = 0;
        if ($post_id) {
            $attachment_id = media_sideload_image($result['url'], $post_id, $result['alt'], 'id');
            // Set featured image if requested and none exists
            if ($featured && !is_wp_error($attachment_id) && !has_post_thumbnail($post_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }

        wp_send_json_success(array(
            'url'        => $result['url'],
            'alt'        => $result['alt'],
            'attachment' => $attachment_id,
        ));
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
    
    /**
     * Enhanced AJAX handler for generator-post-template page
     * Supports both single and bulk generation with templates
     */
    public function ajax_generate_content_enhanced() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        // Get and validate inputs
        $keywords = isset($_POST['keywords']) && is_array($_POST['keywords']) ? array_map('sanitize_text_field', $_POST['keywords']) : array();
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if (empty($keywords)) {
            wp_send_json_error(array('message' => __('Keywords are required', 'kotacom-ai')));
        }
        
        if (empty($template_id)) {
            wp_send_json_error(array('message' => __('Template is required', 'kotacom-ai')));
        }
        
        // Get template content
        $template_post = get_post($template_id);
        if (!$template_post || $template_post->post_type !== 'kotacom_template') {
            wp_send_json_error(array('message' => __('Invalid template', 'kotacom-ai')));
        }
        
        $template_content = $template_post->post_content;
        
        // Get generation parameters
        $parameters = array(
            'tone' => sanitize_text_field($_POST['tone'] ?? 'informative'),
            'length' => sanitize_text_field($_POST['length'] ?? '500'),
            'audience' => sanitize_text_field($_POST['audience'] ?? 'general'),
            'niche' => sanitize_text_field($_POST['niche'] ?? '')
        );
        
        // Get post settings
        $post_settings = array(
            'post_type' => sanitize_text_field($_POST['post_type'] ?? 'post'),
            'post_status' => sanitize_text_field($_POST['post_status'] ?? 'draft'),
            'categories' => isset($_POST['categories']) && is_array($_POST['categories']) ? array_map('intval', $_POST['categories']) : array(),
            'tags' => sanitize_text_field($_POST['tags'] ?? '')
        );
        
        // Provider override
        $provider_override = array();
        if (!empty($_POST['session_provider'])) {
            $provider_override['provider'] = sanitize_text_field($_POST['session_provider']);
            if (!empty($_POST['session_model'])) {
                $provider_override['model'] = sanitize_text_field($_POST['session_model']);
            }
        }
        
        $results = array();
        $success_count = 0;
        $error_count = 0;
        
        // Determine if this is single or bulk generation
        $is_bulk = count($keywords) > 1;
        
        if ($is_bulk) {
            // Bulk generation - use queue system
            $batch_id = 'batch_' . time() . '_' . wp_generate_password(8, false);
            
            // Create batch record
            $this->database->create_batch($batch_id, count($keywords));
            
            foreach ($keywords as $keyword) {
                // Create AI prompt by replacing template placeholders
                $ai_prompt = $this->create_ai_prompt_from_template($template_content, $keyword, $parameters);
                
                // Add to queue
                $queue_item_id = $this->database->add_to_queue($keyword, $ai_prompt, $parameters, $post_settings);
                
                if ($queue_item_id) {
                    $this->database->update_queue_batch_id($queue_item_id, $batch_id);
                    $results[] = array(
                        'keyword' => $keyword,
                        'status' => 'queued',
                        'message' => __('Added to generation queue', 'kotacom-ai'),
                        'queue_id' => $queue_item_id
                    );
                    $success_count++;
                } else {
                    $results[] = array(
                        'keyword' => $keyword,
                        'status' => 'failed',
                        'message' => __('Failed to add to queue', 'kotacom-ai')
                    );
                    $error_count++;
                }
            }
            
            // Start processing the batch
            if ($success_count > 0) {
                $this->background_processor->start_batch_processing($batch_id);
                
                wp_send_json_success(array(
                    'message' => sprintf(__('Bulk generation started! %d items queued for processing.', 'kotacom-ai'), $success_count),
                    'batch_id' => $batch_id,
                    'type' => 'bulk',
                    'results' => $results,
                    'success_count' => $success_count,
                    'error_count' => $error_count
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to start bulk generation', 'kotacom-ai')));
            }
            
        } else {
            // Single generation - process immediately
            $keyword = $keywords[0];
            
            // Create AI prompt by replacing template placeholders
            $ai_prompt = $this->create_ai_prompt_from_template($template_content, $keyword, $parameters);
            
            // Generate content immediately
            $generation_result = $this->api_handler->generate_content($ai_prompt, $parameters);
            
            if ($generation_result['success']) {
                // Replace template placeholders in generated content
                $final_content = str_replace('{keyword}', $keyword, $generation_result['content']);
                
                // Create the post
                $post_data = array(
                    'post_title' => $keyword,
                    'post_content' => $final_content,
                    'post_status' => $post_settings['post_status'],
                    'post_type' => $post_settings['post_type'],
                    'post_author' => get_current_user_id(),
                    'post_date' => !empty($_POST['schedule_date']) ? sanitize_text_field($_POST['schedule_date']) : current_time('mysql')
                );
                
                $post_id = wp_insert_post($post_data);
                
                if ($post_id && !is_wp_error($post_id)) {
                    // Add categories and tags
                    if (!empty($post_settings['categories'])) {
                        wp_set_post_categories($post_id, $post_settings['categories']);
                    }
                    
                    if (!empty($post_settings['tags'])) {
                        wp_set_post_tags($post_id, explode(',', $post_settings['tags']));
                    }
                    
                    wp_send_json_success(array(
                        'message' => __('Content generated successfully!', 'kotacom-ai'),
                        'type' => 'single',
                        'post_id' => $post_id,
                        'edit_link' => get_edit_post_link($post_id),
                        'view_link' => get_permalink($post_id),
                        'keyword' => $keyword
                    ));
                } else {
                    wp_send_json_error(array('message' => __('Failed to create post', 'kotacom-ai')));
                }
            } else {
                wp_send_json_error(array('message' => $generation_result['error']));
            }
        }
    }
    
    /**
     * Create AI prompt from template and keyword
     */
    private function create_ai_prompt_from_template($template_content, $keyword, $parameters) {
        // Create a comprehensive prompt for AI
        $prompt = "Generate content for the keyword: {$keyword}\n\n";
        $prompt .= "Use this template structure as a guide:\n";
        $prompt .= $template_content . "\n\n";
        $prompt .= "Instructions:\n";
        $prompt .= "- Replace {keyword} placeholders with: {$keyword}\n";
        $prompt .= "- Follow the template structure but expand with detailed, relevant content\n";
        $prompt .= "- Tone: " . $parameters['tone'] . "\n";
        $prompt .= "- Target length: " . $parameters['length'] . " words\n";
        $prompt .= "- Target audience: " . $parameters['audience'] . "\n";
        
        if (!empty($parameters['niche'])) {
            $prompt .= "- Industry/Niche: " . $parameters['niche'] . "\n";
        }
        
        $prompt .= "\nGenerate high-quality, engaging content that follows the template structure while being informative and valuable to readers.";
        
        return $prompt;
    }

    /**
     * Bulk Refresh / Content Update
     * POST: post_ids[] (array of IDs), refresh_prompt (string)
     * Prompt can contain placeholders {current_content} and {title}
     */
    public function ajax_refresh_posts() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }

        $post_ids       = isset($_POST['post_ids']) && is_array($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        $refresh_prompt = sanitize_textarea_field($_POST['refresh_prompt'] ?? '');

        if (empty($post_ids) || empty($refresh_prompt)) {
            wp_send_json_error(array('message' => __('Post IDs and refresh prompt are required', 'kotacom-ai')));
        }

        $api_handler = new KotacomAI_API_Handler();
        $results     = array();

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post || $post->post_type === 'revision') {
                $results[] = array('post_id' => $post_id, 'status' => 'error', 'message' => 'Invalid post');
                continue;
            }

            $prompt = str_replace(array('{current_content}', '{title}'), array(wp_strip_all_tags($post->post_content), $post->post_title), $refresh_prompt);

            // Generate refreshed content (simple params)
            $gen = $api_handler->generate_content($prompt, array('tone' => 'informative', 'length' => 'unlimited'));

            if (!$gen['success']) {
                $results[] = array('post_id' => $post_id, 'status' => 'error', 'message' => $gen['error']);
                continue;
            }

            // Save new revision/draft
            $new_post = array(
                'ID'           => $post_id,
                'post_content' => $gen['content'],
            );

            // Save as a revision so editor can compare (or draft override)
            wp_save_post_revision($post_id);
            wp_update_post($new_post);

            $results[] = array('post_id' => $post_id, 'status' => 'success');
        }

        wp_send_json_success(array('results' => $results));
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
