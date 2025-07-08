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
    public $queue_manager;
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
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-queue-manager.php';
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-content-generator.php';
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-template-manager.php'; 
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-template-editor.php';
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-image-generator.php';
        require_once KOTACOM_AI_PLUGIN_DIR . 'includes/class-logger.php';
        
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
        $this->queue_manager = new KotacomAI_Queue_Manager();
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
        
        // Initialize queue processing
        add_action('init', array($this->queue_manager, 'init'));
        
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
        add_action('wp_ajax_kotacom_bulk_edit_tags', array($this, 'ajax_bulk_edit_tags'));
        
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
        add_action('wp_ajax_kotacom_test_image_provider', array($this, 'ajax_test_image_provider'));
        add_action('wp_ajax_kotacom_refresh_posts', array($this, 'ajax_refresh_posts'));
        
        // Gutenberg blocks
        add_action('wp_ajax_kotacom_generate_content_block', array($this, 'ajax_generate_content_block'));
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
        // Clear scheduled cron events
        wp_clear_scheduled_hook('kotacom_ai_process_queue');
        wp_clear_scheduled_hook('kotacom_ai_cleanup_queue');
        
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
        
        // Register Gutenberg blocks
        $this->register_gutenberg_blocks();
    }
    
    /**
     * Register Gutenberg blocks
     */
    private function register_gutenberg_blocks() {
        // Only register blocks if Gutenberg is available
        if (!function_exists('register_block_type')) {
            return;
        }

        // Register block category
        add_filter('block_categories_all', function($categories) {
            return array_merge($categories, array(
                array(
                    'slug' => 'kotacom-ai',
                    'title' => __('Kotacom AI', 'kotacom-ai'),
                    'icon' => 'admin-generic'
                )
            ));
        });

        // Register AI Content Block
        register_block_type(KOTACOM_AI_PLUGIN_DIR . 'blocks/ai-content-block');
        
        // Register AI Image Block
        register_block_type(KOTACOM_AI_PLUGIN_DIR . 'blocks/ai-image-block');

        // Enqueue block assets
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
    }

    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        // Localize script for blocks
        wp_localize_script('wp-blocks', 'kotacomAI', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kotacom_ai_nonce'),
            'pluginUrl' => KOTACOM_AI_PLUGIN_URL
        ));
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
    
    public function ajax_bulk_edit_tags() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $keyword_ids = isset($_POST['keyword_ids']) && is_array($_POST['keyword_ids']) ? array_map('intval', $_POST['keyword_ids']) : array();
        $tag_action = sanitize_text_field($_POST['tag_action'] ?? '');
        $tags = sanitize_text_field($_POST['tags'] ?? '');
        
        if (empty($keyword_ids)) {
            wp_send_json_error(array('message' => __('No keywords selected', 'kotacom-ai')));
        }
        
        if (empty($tag_action)) {
            wp_send_json_error(array('message' => __('Tag action is required', 'kotacom-ai')));
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($keyword_ids as $keyword_id) {
            $keyword_data = $this->database->get_keyword_by_id($keyword_id);
            
            if (!$keyword_data) {
                $error_count++;
                continue;
            }
            
            $current_tags = $keyword_data['tags'] ? explode(',', $keyword_data['tags']) : array();
            $current_tags = array_map('trim', $current_tags);
            $new_tags = $tags ? array_map('trim', explode(',', $tags)) : array();
            
            $updated_tags = array();
            
            switch ($tag_action) {
                case 'replace':
                    $updated_tags = $new_tags;
                    break;
                    
                case 'add':
                    $updated_tags = array_unique(array_merge($current_tags, $new_tags));
                    break;
                    
                case 'remove':
                    $updated_tags = array_diff($current_tags, $new_tags);
                    break;
                    
                default:
                    $error_count++;
                    continue 2;
            }
            
            $updated_tags_string = implode(', ', array_filter($updated_tags));
            
            $result = $this->database->update_keyword($keyword_id, $keyword_data['keyword'], $updated_tags_string);
            
            if ($result) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        if ($success_count > 0) {
            $message = sprintf(__('%d keywords updated successfully', 'kotacom-ai'), $success_count);
            if ($error_count > 0) {
                $message .= sprintf(__(', %d failed', 'kotacom-ai'), $error_count);
            }
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => __('Failed to update keywords', 'kotacom-ai')));
        }
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
        try {
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
            
            // Ensure content generator is initialized
            if (!$this->content_generator) {
                wp_send_json_error(array('message' => __('Content generator not initialized', 'kotacom-ai')));
            }
            
            $result = $this->content_generator->generate_content($keywords, $prompt_template, $parameters, $post_settings, $provider_override);
            
            if ($result && is_array($result) && isset($result['success']) && $result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result ?: array('message' => __('Unknown error occurred', 'kotacom-ai')));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Server error: ', 'kotacom-ai') . $e->getMessage()));
        } catch (Error $e) {
            wp_send_json_error(array('message' => __('Fatal error: ', 'kotacom-ai') . $e->getMessage()));
        }
    }
    
    public function ajax_get_queue_status() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $status = $this->queue_manager->get_queue_status();
        
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
        
        $item_id = sanitize_text_field($_POST['item_id'] ?? '');
        
        if (!empty($item_id)) {
            // Retry specific item
            $result = $this->queue_manager->retry_failed_item($item_id);
            
            if ($result) {
                wp_send_json_success(array('message' => __('Item queued for retry', 'kotacom-ai')));
            } else {
                wp_send_json_error(array('message' => __('Failed to retry item', 'kotacom-ai')));
            }
        } else {
            // Retry all failed items
            $failed_items = $this->queue_manager->get_failed_items();
            $retry_count = 0;
            
            foreach ($failed_items as $item) {
                if ($this->queue_manager->retry_failed_item($item['id'])) {
                    $retry_count++;
                }
            }
            
            if ($retry_count > 0) {
                wp_send_json_success(array('message' => sprintf(__('%d failed items queued for retry', 'kotacom-ai'), $retry_count)));
            } else {
                wp_send_json_error(array('message' => __('No failed items to retry', 'kotacom-ai')));
            }
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
     * POST params: prompt, size (optional), post_id (optional), featured (yes/no), provider (optional), fallback (yes/no)
     */
    public function ajax_generate_image() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }

        $prompt = sanitize_text_field($_POST['prompt'] ?? '');
        $size = sanitize_text_field($_POST['size'] ?? get_option('kotacom_ai_default_image_size', '1024x1024'));
        $post_id = intval($_POST['post_id'] ?? 0);
        $featured = sanitize_text_field($_POST['featured'] ?? 'no') === 'yes';
        $provider = sanitize_text_field($_POST['provider'] ?? get_option('kotacom_ai_default_image_provider', 'unsplash'));
        $enable_fallback = sanitize_text_field($_POST['fallback'] ?? 'yes') === 'yes';

        if (empty($prompt)) {
            wp_send_json_error(array('message' => __('Prompt is required', 'kotacom-ai')));
        }

        $img_gen = new KotacomAI_Image_Generator();
        $result = $img_gen->generate_image($prompt, $size, true, $provider, $enable_fallback);

        if (!$result['success']) {
            if (class_exists('KotacomAI_Logger')) {
                KotacomAI_Logger::add('image_generation', 0, $post_id, $result['error']);
            }
            wp_send_json_error(array('message' => $result['error']));
        }

        $attachment_id = 0;
        $response_data = array(
            'url' => $result['url'],
            'alt' => $result['alt'],
            'provider' => $result['provider'] ?? $provider,
            'attachment_id' => 0
        );

        // Upload to media library if post_id is provided
        if ($post_id) {
            $attachment_id = $img_gen->set_featured_image($post_id, $result['url'], $result['alt']);
            
            if ($attachment_id) {
                $response_data['attachment_id'] = $attachment_id;
                
                // Set as featured image if requested
                if ($featured) {
                    set_post_thumbnail($post_id, $attachment_id);
                    $response_data['featured_set'] = true;
                }
            }
        }

        if (class_exists('KotacomAI_Logger')) {
            KotacomAI_Logger::add(
                'image_generation', 
                1, 
                $post_id, 
                sprintf('Provider: %s, Attachment: %s', $provider, $attachment_id ?: 'N/A')
            );
        }

        wp_send_json_success($response_data);
    }

    /**
     * AJAX: Generate content for Gutenberg block
     */
    public function ajax_generate_content_block() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }

        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        $prompt_id = intval($_POST['prompt'] ?? 0);
        $tone = sanitize_text_field($_POST['tone'] ?? 'informative');
        $length = sanitize_text_field($_POST['length'] ?? '500');
        $audience = sanitize_text_field($_POST['audience'] ?? 'general');

        if (empty($keyword)) {
            wp_send_json_error(array('message' => __('Keyword is required', 'kotacom-ai')));
        }

        // Get prompt template if provided
        $prompt_template = '';
        if ($prompt_id) {
            $prompt_post = get_post($prompt_id);
            if ($prompt_post && $prompt_post->post_type === 'kotacom_template') {
                $prompt_template = $prompt_post->post_content;
            }
        }

        // Create default prompt if none provided
        if (empty($prompt_template)) {
            $prompt_template = "Write a comprehensive, engaging {tone} article about {keyword} for {audience}. Make it approximately {length} words long and ensure it's informative and valuable to readers.";
        }

        // Replace placeholders
        $final_prompt = str_replace(
            array('{keyword}', '{tone}', '{length}', '{audience}'),
            array($keyword, $tone, $length, $audience),
            $prompt_template
        );

        // Generate content
        $api_handler = new KotacomAI_API_Handler();
        $result = $api_handler->generate_content($final_prompt, array(
            'tone' => $tone,
            'length' => $length,
            'audience' => $audience
        ));

        if ($result['success']) {
            // Log successful generation
            if (class_exists('KotacomAI_Logger')) {
                KotacomAI_Logger::add(
                    'block_content_generation', 
                    1, 
                    0, 
                    sprintf('Keyword: %s, Length: %s words', $keyword, $length)
                );
            }

            wp_send_json_success(array(
                'content' => $result['content'],
                'keyword' => $keyword
            ));
        } else {
            // Log failed generation
            if (class_exists('KotacomAI_Logger')) {
                KotacomAI_Logger::add(
                    'block_content_generation', 
                    0, 
                    0, 
                    sprintf('Failed for keyword: %s - %s', $keyword, $result['error'])
                );
            }

            wp_send_json_error(array('message' => $result['error']));
        }
    }

    /**
     * AJAX: Test Image Provider Connection
     */
    public function ajax_test_image_provider() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }

        $provider = sanitize_text_field($_POST['provider'] ?? '');

        if (empty($provider)) {
            wp_send_json_error(array('message' => __('Provider is required', 'kotacom-ai')));
        }

        $img_gen = new KotacomAI_Image_Generator();
        $result = $img_gen->test_provider($provider);

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => sprintf(__('%s connection successful!', 'kotacom-ai'), ucfirst($provider)),
                'provider' => $provider,
                'url' => $result['url']
            ));
        } else {
            wp_send_json_error(array(
                'message' => sprintf(__('%s connection failed: %s', 'kotacom-ai'), ucfirst($provider), $result['error']),
                'provider' => $provider
            ));
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
    
    /**
     * Enhanced AJAX handler for generator-post-template page
     * Supports both single and bulk generation with templates
     */
    public function ajax_generate_content_enhanced() {
        try {
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
            
            // Ensure required components are initialized
            if (!$this->queue_manager) {
                wp_send_json_error(array('message' => __('Queue manager not initialized', 'kotacom-ai')));
            }
            
            if (!$this->api_handler) {
                wp_send_json_error(array('message' => __('API handler not initialized', 'kotacom-ai')));
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
            
            foreach ($keywords as $keyword) {
                // Create AI prompt by replacing template placeholders
                $ai_prompt = $this->create_ai_prompt_from_template($template_content, $keyword, $parameters);
                
                // Add to queue using queue manager
                $queue_item_id = $this->queue_manager->add_single_item_to_queue('generate_content', array(
                    'keyword' => $keyword,
                    'prompt' => $ai_prompt,
                    'params' => $parameters,
                    'create_post' => true,
                    'post_status' => $post_settings['post_status'] ?? 'draft',
                    'post_type' => $post_settings['post_type'] ?? 'post',
                    'categories' => $post_settings['categories'] ?? array(),
                    'tags' => $post_settings['tags'] ?? '',
                    'batch_id' => $batch_id
                ), 10);
                
                if ($queue_item_id) {
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
                $this->queue_manager->start_batch_processing($batch_id);
                
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
        
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Server error: ', 'kotacom-ai') . $e->getMessage()));
        } catch (Error $e) {
            wp_send_json_error(array('message' => __('Fatal error: ', 'kotacom-ai') . $e->getMessage()));
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
     * Bulk Refresh / Content Update - Now uses Queue System
     * POST: post_ids[] (array of IDs), refresh_prompt (string)
     * Prompt can contain placeholders {current_content} and {title}
     */
    public function ajax_refresh_posts() {
        try {
            check_ajax_referer('kotacom_ai_nonce', 'nonce');

            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
            }

        $post_ids       = isset($_POST['post_ids']) && is_array($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        $refresh_prompt = sanitize_textarea_field($_POST['refresh_prompt'] ?? '');
        $template_id    = intval($_POST['template_id'] ?? 0);
        $update_date    = sanitize_text_field($_POST['update_date'] ?? 'no') === 'yes';

        if (empty($post_ids) || empty($refresh_prompt)) {
            wp_send_json_error(array('message' => __('Post IDs and refresh prompt are required', 'kotacom-ai')));
        }

        $results = array();
        $success_count = 0;
        $error_count = 0;
        
        // Determine if this is single or bulk refresh
        $is_bulk = count($post_ids) > 1;
        
        if ($is_bulk) {
            // Bulk refresh - use queue system
            $batch_id = 'refresh_batch_' . time() . '_' . wp_generate_password(8, false);
            
            foreach ($post_ids as $post_id) {
                $post = get_post($post_id);
                if (!$post || $post->post_type === 'revision') {
                    $results[] = array('post_id' => $post_id, 'status' => 'error', 'message' => 'Invalid post');
                    $error_count++;
                    continue;
                }

                $prompt_base = $refresh_prompt;
                if ($template_id) {
                    $template_post = get_post($template_id);
                    if ($template_post) { 
                        $prompt_base = $template_post->post_content; 
                    }
                }

                // Add to queue for background processing
                $queue_item_id = $this->queue_manager->add_single_item_to_queue('refresh_content', array(
                    'post_id' => $post_id,
                    'refresh_prompt' => $prompt_base,
                    'update_date' => $update_date,
                    'batch_id' => $batch_id
                ), 5); // Priority 5 for refresh tasks
                
                if ($queue_item_id) {
                    $results[] = array(
                        'post_id' => $post_id,
                        'status' => 'queued',
                        'message' => __('Added to refresh queue', 'kotacom-ai'),
                        'queue_id' => $queue_item_id
                    );
                    $success_count++;
                } else {
                    $results[] = array(
                        'post_id' => $post_id,
                        'status' => 'error',
                        'message' => __('Failed to add to queue', 'kotacom-ai')
                    );
                    $error_count++;
                }
            }
            
            if ($success_count > 0) {
                wp_send_json_success(array(
                    'message' => sprintf(__('Bulk refresh started! %d posts queued for processing.', 'kotacom-ai'), $success_count),
                    'batch_id' => $batch_id,
                    'type' => 'bulk',
                    'results' => $results,
                    'success_count' => $success_count,
                    'error_count' => $error_count
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to start bulk refresh', 'kotacom-ai')));
            }
            
        } else {
            // Single refresh - process immediately
            $post_id = $post_ids[0];
            $post = get_post($post_id);
            
            if (!$post || $post->post_type === 'revision') {
                wp_send_json_error(array('message' => __('Invalid post', 'kotacom-ai')));
            }

            $prompt_base = $refresh_prompt;
            if ($template_id) {
                $template_post = get_post($template_id);
                if ($template_post) { 
                    $prompt_base = $template_post->post_content; 
                }
            }

            $prompt = str_replace(
                array('{current_content}', '{title}', '{published_date}'),
                array(wp_strip_all_tags($post->post_content), $post->post_title, get_the_date('', $post)),
                $prompt_base
            );

            // Generate refreshed content (simple params)
            $api_handler = new KotacomAI_API_Handler();
            $gen = $api_handler->generate_content($prompt, array('tone' => 'informative', 'length' => 'unlimited'));

            if (!$gen['success']) {
                KotacomAI_Logger::add('refresh', 0, $post_id, $gen['error']);
                wp_send_json_error(array('message' => $gen['error']));
            }

            // Save new revision/draft
            $new_post = array(
                'ID'           => $post_id,
                'post_content' => $gen['content'],
            );

            // Save as a revision so editor can compare (or draft override)
            wp_save_post_revision($post_id);

            if ($update_date) {
                $new_post['post_date'] = current_time('mysql');
            }
            
            $updated = wp_update_post($new_post);
            
            if ($updated && !is_wp_error($updated)) {
                KotacomAI_Logger::add('refresh', 1, $post_id, 'OK');
                wp_send_json_success(array(
                    'message' => __('Content refreshed successfully!', 'kotacom-ai'),
                    'type' => 'single',
                    'post_id' => $post_id,
                    'edit_link' => get_edit_post_link($post_id),
                    'view_link' => get_permalink($post_id)
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to update post', 'kotacom-ai')));
            }
        }
        
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Server error: ', 'kotacom-ai') . $e->getMessage()));
        } catch (Error $e) {
            wp_send_json_error(array('message' => __('Fatal error: ', 'kotacom-ai') . $e->getMessage()));
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
