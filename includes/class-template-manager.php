<?php
/**
 * Advanced Template Manager for AI Content
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Template_Manager {
    
    private $database;
    private $template_types = array(
        'shortcode' => 'Shortcode-based Templates',
        'gutenberg' => 'Gutenberg Block Templates', 
        'visual' => 'Visual Drag & Drop Templates',
        // 'json' => 'JSON Structure Templates' // Removed as it's not implemented
    );
    
    public function __construct() {
        $this->database = new KotacomAI_Database();
        $this->init();
    }
    
    private function init() {
        // Register template post type
        add_action('init', array($this, 'register_template_post_type'));
        
        // Register Gutenberg blocks
        add_action('init', array($this, 'register_gutenberg_blocks'));
        
        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));
        
        // AJAX handlers
        add_action('wp_ajax_kotacom_save_template', array($this, 'ajax_save_template'));
        add_action('wp_ajax_kotacom_preview_template', array($this, 'ajax_preview_template'));
        add_action('wp_ajax_kotacom_duplicate_template', array($this, 'ajax_duplicate_template'));
        add_action('wp_ajax_kotacom_get_template', array($this, 'ajax_get_template')); // New AJAX for loading single template
        add_action('wp_ajax_kotacom_get_templates', array($this, 'ajax_get_templates')); // New AJAX for getting all templates
    }
    
    /**
     * Register custom post type for templates
     */
    public function register_template_post_type() {
        register_post_type('kotacom_template', array(
            'labels' => array(
                'name' => __('AI Content Templates', 'kotacom-ai'),
                'singular_name' => __('Template', 'kotacom-ai'),
                'add_new' => __('Add New Template', 'kotacom-ai'),
                'add_new_item' => __('Add New Template', 'kotacom-ai'),
                'edit_item' => __('Edit Template', 'kotacom-ai'),
                'all_items' => __('All Templates', 'kotacom-ai'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'kotacom-ai',
            'capability_type' => 'post',
            'supports' => array('title', 'editor', 'custom-fields'),
            'show_in_rest' => true, // Enable Gutenberg
        ));
    }
    
    /**
     * Register Gutenberg blocks for AI content
     */
    public function register_gutenberg_blocks() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // AI Content Block
        register_block_type('kotacom-ai/content-block', array(
            'editor_script' => 'kotacom-ai-blocks', // Assuming this script exists and registers blocks
            'render_callback' => array($this, 'render_ai_content_block'),
            'attributes' => array(
                'contentType' => array(
                    'type' => 'string',
                    'default' => 'paragraph'
                ),
                'prompt' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'parameters' => array(
                    'type' => 'object',
                    'default' => array()
                ),
                'placeholder' => array(
                    'type' => 'string',
                    'default' => 'AI content will be generated here...'
                )
            )
        ));
        
        // AI List Block (New)
        register_block_type('kotacom-ai/list-block', array(
            'editor_script' => 'kotacom-ai-blocks',
            'render_callback' => array($this, 'render_ai_list_block'),
            'attributes' => array(
                'prompt' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'listType' => array(
                    'type' => 'string',
                    'default' => 'ul'
                ),
                'length' => array(
                    'type' => 'number',
                    'default' => 5
                ),
                'itemPrefix' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'itemSuffix' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));

        // Template Structure Block (Placeholder, assuming it's a container)
        register_block_type('kotacom-ai/template-structure', array(
            'editor_script' => 'kotacom-ai-blocks',
            'render_callback' => array($this, 'render_template_structure'), // This can be a simple div wrapper
            'attributes' => array(
                'sections' => array(
                    'type' => 'array',
                    'default' => array()
                ),
                'layout' => array(
                    'type' => 'string',
                    'default' => 'standard'
                )
            )
        ));
    }
    
    /**
     * Register shortcodes for template system
     */
    public function register_shortcodes() {
        add_shortcode('ai_content', array($this, 'shortcode_ai_content'));
        add_shortcode('ai_section', array($this, 'shortcode_ai_section'));
        add_shortcode('ai_template', array($this, 'shortcode_ai_template'));
        add_shortcode('ai_conditional', array($this, 'shortcode_ai_conditional'));
        add_shortcode('ai_list', array($this, 'shortcode_ai_list')); // New shortcode
        add_shortcode('ai_image', array($this, 'shortcode_ai_image')); // Image shortcode
    }
    
    /**
     * AI Content shortcode handler
     */
    public function shortcode_ai_content($atts, $content = '') {
        $atts = shortcode_atts(array(
            'type' => 'paragraph',
            'prompt' => '',
            'length' => '200',
            'tone' => 'informative',
            'keyword' => '{keyword}', // This will be replaced by the template manager
            'cache' => 'true',
            'fallback' => ''
        ), $atts);
        
        // Replace {keyword} if it's still present (e.g., if shortcode is used directly)
        $atts['prompt'] = str_replace('{keyword}', $this->get_current_keyword(), $atts['prompt']);

        // Generate unique cache key
        $cache_key = 'ai_content_' . md5(serialize($atts));
        
        if ($atts['cache'] === 'true') {
            $cached_content = get_transient($cache_key);
            if ($cached_content !== false) {
                return $cached_content;
            }
        }
        
        // Generate AI content
        $generated_content = $this->generate_ai_content($atts);
        
        if ($atts['cache'] === 'true' && !empty($generated_content)) {
            // Cache duration from template settings, or default to 1 hour
            $template_settings = json_decode(get_post_meta(get_the_ID(), 'template_settings', true), true);
            $cache_duration = isset($template_settings['cache_duration']) ? intval($template_settings['cache_duration']) : HOUR_IN_SECONDS;
            set_transient($cache_key, $generated_content, $cache_duration);
        }
        
        // Wrap content based on type
        $output = '';
        if (!empty($generated_content)) {
            switch ($atts['type']) {
                case 'heading':
                    $output = '<h2 class="ai-generated-heading">' . esc_html($generated_content) . '</h2>';
                    break;
                case 'list':
                    $items = explode("\n", $generated_content);
                    $output = '<ul class="ai-generated-list">';
                    foreach ($items as $item) {
                        if (!empty(trim($item))) {
                            $output .= '<li>' . esc_html(trim($item)) . '</li>';
                        }
                    }
                    $output .= '</ul>';
                    break;
                case 'paragraph':
                default:
                    $output = '<div class="ai-generated-paragraph">' . wpautop($generated_content) . '</div>';
                    break;
            }
        }
        
        return !empty($output) ? $output : $atts['fallback'];
    }
    
    /**
     * AI Section shortcode for structured content
     */
    public function shortcode_ai_section($atts, $content = '') {
        $atts = shortcode_atts(array(
            'title' => '',
            'class' => 'ai-section',
            'wrapper' => 'div'
        ), $atts);
        
        // Replace {keyword} in title
        $atts['title'] = str_replace('{keyword}', $this->get_current_keyword(), $atts['title']);

        $output = '<' . esc_attr($atts['wrapper']) . ' class="' . esc_attr($atts['class']) . '">';
        
        if (!empty($atts['title'])) {
            $output .= '<h3 class="ai-section-title">' . esc_html($atts['title']) . '</h3>';
        }
        
        $output .= do_shortcode($content);
        $output .= '</' . esc_attr($atts['wrapper']) . '>';
        
        return $output;
    }

    /**
     * AI List shortcode handler (New)
     */
    public function shortcode_ai_list($atts, $content = '') {
        $atts = shortcode_atts(array(
            'prompt' => '',
            'type' => 'ul', // ul or ol
            'length' => '5', // Number of items
            'item_prefix' => '',
            'item_suffix' => '',
            'cache' => 'true',
            'fallback' => ''
        ), $atts);

        // Replace {keyword} in prompt
        $atts['prompt'] = str_replace('{keyword}', $this->get_current_keyword(), $atts['prompt']);

        $cache_key = 'ai_list_' . md5(serialize($atts));
        if ($atts['cache'] === 'true') {
            $cached_content = get_transient($cache_key);
            if ($cached_content !== false) {
                return $cached_content;
            }
        }

        // Generate AI content for the list
        $list_prompt = $atts['prompt'] . " (Generate " . intval($atts['length']) . " items, one per line)";
        $generated_content = $this->generate_ai_content(array('prompt' => $list_prompt, 'length' => $atts['length']));

        if (empty($generated_content)) {
            return $atts['fallback'];
        }

        $items = array_filter(array_map('trim', explode("\n", $generated_content)));
        if (empty($items)) {
            return $atts['fallback'];
        }

        $output = '<' . esc_attr($atts['type']) . ' class="ai-generated-list">';
        foreach ($items as $item) {
            $output .= '<li>' . esc_html($atts['item_prefix']) . esc_html($item) . esc_html($atts['item_suffix']) . '</li>';
        }
        $output .= '</' . esc_attr($atts['type']) . '>';

        if ($atts['cache'] === 'true') {
            $template_settings = json_decode(get_post_meta(get_the_ID(), 'template_settings', true), true);
            $cache_duration = isset($template_settings['cache_duration']) ? intval($template_settings['cache_duration']) : HOUR_IN_SECONDS;
            set_transient($cache_key, $output, $cache_duration);
        }

        return $output;
    }
    
    /**
     * AI Image shortcode handler
     * Usage: [ai_image prompt="A sunset over mountains" size="1024x1024" featured="yes" alt=""]
     */
    public function shortcode_ai_image($atts, $content = '') {
        $atts = shortcode_atts(array(
            'prompt'   => '',
            'size'     => '1024x1024',
            'alt'      => '',
            'featured' => 'no', // yes/no – set as featured image
        ), $atts);

        if (empty($atts['prompt'])) {
            return '<!-- ai_image: prompt missing -->';
        }

        // Replace {keyword} placeholder
        if (strpos($atts['prompt'], '{keyword}') !== false) {
            $atts['prompt'] = str_replace('{keyword}', $this->get_current_keyword(), $atts['prompt']);
        }

        $img_gen = new KotacomAI_Image_Generator();
        $result  = $img_gen->generate_image($atts['prompt'], $atts['size'], empty($atts['alt']));

        if (!$result['success']) {
            return '<!-- ai_image error: ' . esc_html($result['error']) . ' -->';
        }

        $img_url = esc_url($result['url']);
        $alt     = !empty($atts['alt']) ? esc_attr($atts['alt']) : esc_attr($result['alt']);

        // Optionally set as featured image (only on singular post edit screen)
        if ($atts['featured'] === 'yes' && is_singular()) {
            global $post;
            if ($post && !has_post_thumbnail($post->ID)) {
                if (!function_exists('media_sideload_image')) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                }
                // Sideload image and set as featured
                $attachment_id = media_sideload_image($img_url, $post->ID, $alt, 'id');
                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($post->ID, $attachment_id);
                }
            }
        }

        // Return <img> tag for embedding
        return '<img class="ai-generated-image" src="' . $img_url . '" alt="' . $alt . '" loading="lazy" />';
    }
    
    /**
     * Template shortcode for loading saved templates
     */
    public function shortcode_ai_template($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'name' => '',
            'keyword' => '{keyword}', // Default placeholder
            'variables' => '' // JSON string of variables
        ), $atts);
        
        $template = null;
        
        if (!empty($atts['id'])) {
            $template = get_post($atts['id']);
        } elseif (!empty($atts['name'])) {
            $template = get_page_by_title($atts['name'], OBJECT, 'kotacom_template');
        }
        
        if (!$template) {
            return '<!-- Template not found -->';
        }
        
        $template_content = $template->post_content;
        
        // Replace variables
        $variables_from_shortcode = array();
        if (!empty($atts['variables'])) {
            $decoded_vars = json_decode($atts['variables'], true);
            if (is_array($decoded_vars)) {
                $variables_from_shortcode = $decoded_vars;
            }
        }

        // Merge with template's stored variables (if any)
        $stored_variables = json_decode(get_post_meta($template->ID, 'template_variables', true), true);
        if (is_array($stored_variables)) {
            foreach ($stored_variables as $var_name => $var_data) {
                // Use value from shortcode if provided, otherwise use default from stored variables
                $value_to_use = isset($variables_from_shortcode[$var_name]) ? $variables_from_shortcode[$var_name] : $var_data['default'];
                $template_content = str_replace('{' . $var_name . '}', esc_html($value_to_use), $template_content);
            }
        }
        
        // Replace keyword
        $final_keyword = !empty($atts['keyword']) && $atts['keyword'] !== '{keyword}' ? $atts['keyword'] : $this->get_current_keyword();
        $template_content = str_replace('{keyword}', esc_html($final_keyword), $template_content);
        
        return do_shortcode($template_content);
    }
    
    /**
     * Conditional content shortcode
     */
    public function shortcode_ai_conditional($atts, $content = '') {
        $atts = shortcode_atts(array(
            'if' => '', // Variable to check (e.g., 'post_type', 'user_role', 'custom_field')
            'equals' => '', // Value to compare 'if' variable against
            'contains' => '', // Value that 'if' variable should contain
            'not_empty' => '', // Check if a variable is not empty
            'user_role' => '', // Check current user role
            'post_type' => '' // Check current post type
        ), $atts);
        
        $show_content = false;
        
        // Check conditions
        if (!empty($atts['user_role'])) {
            $show_content = current_user_can($atts['user_role']);
        } elseif (!empty($atts['post_type'])) {
            $show_content = get_post_type() === $atts['post_type'];
        } elseif (!empty($atts['if'])) {
            $variable_value = $this->get_template_variable($atts['if']);
            
            if (!empty($atts['equals'])) {
                $show_content = ($variable_value === $atts['equals']);
            } elseif (!empty($atts['contains'])) {
                $show_content = (strpos($variable_value, $atts['contains']) !== false);
            } elseif (!empty($atts['not_empty'])) {
                $show_content = !empty($variable_value);
            }
        }
        
        return $show_content ? do_shortcode($content) : '';
    }
    
    /**
     * Generate AI content based on parameters
     */
    private function generate_ai_content($params) {
        $api_handler = new KotacomAI_API_Handler();
        
        $prompt = $params['prompt'];
        if (empty($prompt)) {
            return '';
        }
        
        $generation_params = array(
            'tone' => $params['tone'] ?? get_option('kotacom_ai_default_tone', 'informative'),
            'length' => $params['length'] ?? get_option('kotacom_ai_default_length', '500'),
            'audience' => $params['audience'] ?? get_option('kotacom_ai_default_audience', 'general'),
            'niche' => $params['niche'] ?? '',
        );
        
        $result = $api_handler->generate_content($prompt, $generation_params);
        
        return $result['success'] ? $result['content'] : '';
    }
    
    /**
     * Get template variable value
     * This function needs to be aware of the context (e.g., current post, global variables)
     */
    private function get_template_variable($variable_name) {
        global $post;
        
        // First, check if it's a standard WordPress post property
        switch ($variable_name) {
            case 'post_title':
                return $post ? $post->post_title : '';
            case 'post_type':
                return get_post_type();
            case 'user_role':
                $user = wp_get_current_user();
                return !empty($user->roles) ? $user->roles[0] : '';
            case 'site_url':
                return get_site_url();
            case 'blog_name':
                return get_bloginfo('name');
            case 'current_date':
                return date_i18n(get_option('date_format'));
            case 'current_time':
                return date_i18n(get_option('time_format'));
            default:
                // Then, check if it's a custom field of the current post
                if ($post) {
                    $custom_field_value = get_post_meta($post->ID, $variable_name, true);
                    if (!empty($custom_field_value)) {
                        return $custom_field_value;
                    }
                }
                // Finally, check if it's a variable defined in the template itself
                // This requires the template to be loaded and its variables accessible
                $template_id = get_the_ID(); // Assuming we are in the context of a template post
                if ($template_id && get_post_type($template_id) === 'kotacom_template') {
                    $template_variables = json_decode(get_post_meta($template_id, 'template_variables', true), true);
                    if (isset($template_variables[$variable_name])) {
                        return $template_variables[$variable_name]['default'];
                    }
                }
                return ''; // Return empty if not found
        }
    }

    /**
     * Get the current keyword being processed (for preview or generation)
     * This is a helper to make shortcodes work correctly in preview/generation context.
     */
    private function get_current_keyword() {
        // For AJAX preview, keyword is passed in $_POST
        if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['keyword'])) {
            return sanitize_text_field($_POST['keyword']);
        }
        // For actual content generation, keyword is passed via a global or action hook
        // This is a simplified approach; a more robust solution might use a global context object
        global $kotacom_ai_current_keyword;
        if (isset($kotacom_ai_current_keyword)) {
            return $kotacom_ai_current_keyword;
        }
        return ''; // Default empty
    }
    
    /**
     * Render Gutenberg AI content block
     */
    public function render_ai_content_block($attributes, $content) {
        $content_type = $attributes['contentType'] ?? 'paragraph';
        $prompt = $attributes['prompt'] ?? '';
        $parameters = $attributes['parameters'] ?? array();
        $placeholder = $attributes['placeholder'] ?? 'AI content will be generated here...';
        
        if (empty($prompt)) {
            return '<div class="ai-content-placeholder">' . esc_html($placeholder) . '</div>';
        }
        
        // Generate content
        $generated_content = $this->generate_ai_content(array_merge($parameters, array('prompt' => $prompt)));
        
        if (empty($generated_content)) {
            return '<div class="ai-content-error">Failed to generate content</div>';
        }
        
        // Wrap content based on type
        switch ($content_type) {
            case 'heading':
                return '<h2 class="ai-generated-heading">' . esc_html($generated_content) . '</h2>';
            case 'list':
                $items = explode("\n", $generated_content);
                $output = '<ul class="ai-generated-list">';
                foreach ($items as $item) {
                    if (!empty(trim($item))) {
                        $output .= '<li>' . esc_html(trim($item)) . '</li>';
                    }
                }
                $output .= '</ul>';
                return $output;
            default:
                return '<div class="ai-generated-content">' . wpautop($generated_content) . '</div>';
        }
    }

    /**
     * Render Gutenberg AI List block (New)
     */
    public function render_ai_list_block($attributes) {
        $prompt = $attributes['prompt'] ?? '';
        $list_type = $attributes['listType'] ?? 'ul';
        $length = $attributes['length'] ?? 5;
        $item_prefix = $attributes['itemPrefix'] ?? '';
        $item_suffix = $attributes['itemSuffix'] ?? '';

        if (empty($prompt)) {
            return '<div class="ai-list-placeholder">AI list will be generated here...</div>';
        }

        $list_prompt = $prompt . " (Generate " . intval($length) . " items, one per line)";
        $generated_content = $this->generate_ai_content(array('prompt' => $list_prompt, 'length' => $length));

        if (empty($generated_content)) {
            return '<div class="ai-list-error">Failed to generate list content</div>';
        }

        $items = array_filter(array_map('trim', explode("\n", $generated_content)));
        if (empty($items)) {
            return '<div class="ai-list-empty">No list items generated.</div>';
        }

        $output = '<' . esc_attr($list_type) . ' class="ai-generated-list">';
        foreach ($items as $item) {
            $output .= '<li>' . esc_html($item_prefix) . esc_html($item) . esc_html($item_suffix) . '</li>';
        }
        $output .= '</' . esc_attr($list_type) . '>';

        return $output;
    }

    /**
     * Render Gutenberg Template Structure block (simple wrapper)
     */
    public function render_template_structure($attributes, $content) {
        $layout_class = 'ai-template-layout-' . ($attributes['layout'] ?? 'standard');
        return '<div class="ai-template-structure ' . esc_attr($layout_class) . '">' . $content . '</div>';
    }
    
    /**
     * Save template via AJAX
     */
    public function ajax_save_template() {
        error_log('KotacomAI: ajax_save_template called.');
        error_log('KotacomAI: POST data: ' . print_r($_POST, true));

        check_ajax_referer('kotacom_ai_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            error_log('KotacomAI: ajax_save_template - Insufficient permissions.');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }

        $template_id = intval($_POST['id'] ?? 0);

        $template_data = array(
            'post_title' => sanitize_text_field($_POST['title'] ?? ''),
            'post_content' => wp_kses_post($_POST['content'] ?? ''),
            'post_type' => 'kotacom_template',
            'post_status' => 'publish',
        );

        $meta_input = array(
            'template_type' => sanitize_text_field($_POST['template_type'] ?? 'shortcode'),
            'template_settings' => json_encode($_POST['settings'] ?? array()),
            'template_variables' => json_encode($_POST['variables'] ?? array())
        );

        // If updating an existing post
        if ($template_id > 0) {
            $template_data['ID'] = $template_id;
            error_log('KotacomAI: Attempting to update template ID: ' . $template_id);
            $result = wp_update_post($template_data, true);
            error_log('KotacomAI: wp_update_post result: ' . print_r($result, true));
            // Update meta fields separately for existing posts
            foreach ($meta_input as $key => $value) {
                update_post_meta($template_id, $key, $value);
                error_log("KotacomAI: Updated meta '$key' for template ID $template_id.");
            }
        } else {
            // If creating a new post
            $template_data['meta_input'] = $meta_input;
            error_log('KotacomAI: Attempting to insert new template.');
            $result = wp_insert_post($template_data, true);
            error_log('KotacomAI: wp_insert_post result: ' . print_r($result, true));
        }

        if (!is_wp_error($result)) {
            error_log('KotacomAI: Template saved successfully. ID: ' . $result);
            wp_send_json_success(array(
                'message' => __('Template saved successfully', 'kotacom-ai'),
                'template_id' => $result // This will be the post ID
            ));
        } else {
            error_log('KotacomAI: Failed to save template. Error: ' . $result->get_error_message());
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
    }
    
    /**
     * Preview template via AJAX
     */
    public function ajax_preview_template() {
        error_log('KotacomAI: ajax_preview_template called.');
        error_log('KotacomAI: POST data: ' . print_r($_POST, true));

        check_ajax_referer('kotacom_ai_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            error_log('KotacomAI: ajax_preview_template - Insufficient permissions.');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }

        $content = wp_kses_post($_POST['content'] ?? '');
        $keyword = sanitize_text_field($_POST['keyword'] ?? 'example keyword');
        $variables_json = wp_unslash($_POST['variables'] ?? '{}'); // Get JSON string
        $variables = json_decode($variables_json, true);

        error_log('KotacomAI: Previewing with keyword: ' . $keyword);
        error_log('KotacomAI: Previewing with variables: ' . print_r($variables, true));

        // Set global keyword for shortcode processing context
        global $kotacom_ai_current_keyword;
        $kotacom_ai_current_keyword = $keyword;

        // Replace variables in content
        $preview_content = $content;
        if (is_array($variables)) {
            foreach ($variables as $key => $data) {
                $value = $data['default'] ?? ''; // Use default value from variable manager
                $preview_content = str_replace('{' . $key . '}', sanitize_text_field($value), $preview_content);
            }
        }

        error_log('KotacomAI: Content before shortcode processing: ' . $preview_content);

        // Process shortcodes
        $preview_content = do_shortcode($preview_content);

        error_log('KotacomAI: Content after shortcode processing: ' . $preview_content);

        // Clear global keyword
        unset($kotacom_ai_current_keyword);

        wp_send_json_success(array('preview' => $preview_content));
        error_log('KotacomAI: ajax_preview_template finished successfully.');
    }

    /**
     * Duplicate template via AJAX (New)
     */
    public function ajax_duplicate_template() {
        error_log('KotacomAI: ajax_duplicate_template called.');
        error_log('KotacomAI: POST data: ' . print_r($_POST, true));

        check_ajax_referer('kotacom_ai_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            error_log('KotacomAI: ajax_duplicate_template - Insufficient permissions.');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }

        $template_id = intval($_POST['template_id'] ?? 0);
        if (!$template_id) {
            error_log('KotacomAI: ajax_duplicate_template - Template ID is required.');
            wp_send_json_error(array('message' => __('Template ID is required for duplication.', 'kotacom-ai')));
        }

        $original_template = get_post($template_id);
        if (!$original_template || $original_template->post_type !== 'kotacom_template') {
            error_log('KotacomAI: ajax_duplicate_template - Original template not found or wrong type. ID: ' . $template_id);
            wp_send_json_error(array('message' => __('Original template not found.', 'kotacom-ai')));
        }

        $new_template_data = array(
            'post_title'    => $original_template->post_title . ' (Copy)',
            'post_content'  => $original_template->post_content,
            'post_status'   => 'draft', // Duplicate as draft
            'post_type'     => 'kotacom_template',
            'post_author'   => get_current_user_id(),
        );

        error_log('KotacomAI: Attempting to insert duplicated template.');
        $new_template_id = wp_insert_post($new_template_data, true);
        error_log('KotacomAI: wp_insert_post result for duplication: ' . print_r($new_template_id, true));


        if (is_wp_error($new_template_id)) {
            error_log('KotacomAI: Failed to duplicate template. Error: ' . $new_template_id->get_error_message());
            wp_send_json_error(array('message' => $new_template_id->get_error_message()));
        }

        // Duplicate custom fields (meta data)
        $custom_fields = get_post_custom($template_id);
        error_log('KotacomAI: Duplicating custom fields for template ID ' . $template_id . ' to ' . $new_template_id);
        foreach ($custom_fields as $key => $values) {
            // Skip internal WP meta keys and the original ID
            if (strpos($key, '_') !== 0 || in_array($key, ['_edit_lock', '_edit_last'])) {
                continue;
            }
            foreach ($values as $value) {
                add_post_meta($new_template_id, $key, maybe_unserialize($value));
                error_log("KotacomAI: Added meta '$key' to new template ID $new_template_id.");
            }
        }

        error_log('KotacomAI: Template duplicated successfully. New ID: ' . $new_template_id);
        wp_send_json_success(array(
            'message' => __('Template duplicated successfully!', 'kotacom-ai'),
            'new_template_id' => $new_template_id
        ));
    }

    /**
     * Get a single template via AJAX (New)
     */
    public function ajax_get_template() {
        error_log('KotacomAI: ajax_get_template called.');
        error_log('KotacomAI: POST data: ' . print_r($_POST, true));

        check_ajax_referer('kotacom_ai_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            error_log('KotacomAI: ajax_get_template - Insufficient permissions.');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }

        $template_id = intval($_POST['template_id'] ?? 0);
        if (!$template_id) {
            error_log('KotacomAI: ajax_get_template - Template ID is required.');
            wp_send_json_error(array('message' => __('Template ID is required.', 'kotacom-ai')));
        }

        $template = get_post($template_id);
        error_log('KotacomAI: get_post result for ID ' . $template_id . ': ' . print_r($template, true));

        if (!$template || $template->post_type !== 'kotacom_template') {
            error_log('KotacomAI: ajax_get_template - Template not found or wrong type. ID: ' . $template_id);
            wp_send_json_error(array('message' => __('Template not found.', 'kotacom-ai')));
        }

        // Get meta data
        $template_type = get_post_meta($template_id, 'template_type', true);
        $template_settings = get_post_meta($template_id, 'template_settings', true);
        $template_variables = get_post_meta($template_id, 'template_variables', true);

        error_log('KotacomAI: Retrieved template data for ID ' . $template_id);
        error_log('KotacomAI: Type: ' . $template_type);
        error_log('KotacomAI: Settings: ' . $template_settings);
        error_log('KotacomAI: Variables: ' . $template_variables);


        wp_send_json_success(array(
            'template' => array(
                'ID' => $template->ID,
                'title' => $template->post_title,
                'content' => $template->post_content,
                'template_type' => $template_type,
                'settings' => $template_settings,
                'variables' => $template_variables,
            )
        ));
        error_log('KotacomAI: ajax_get_template finished successfully.');
    }

    /**
     * Get all available templates via AJAX (New)
     */
    public function ajax_get_templates() {
        error_log('KotacomAI: ajax_get_templates called.');
        error_log('KotacomAI: POST data: ' . print_r($_POST, true));

        check_ajax_referer('kotacom_ai_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            error_log('KotacomAI: ajax_get_templates - Insufficient permissions.');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }

        $templates = $this->get_templates(); // Use existing method

        error_log('KotacomAI: Retrieved ' . count($templates) . ' templates.');

        wp_send_json_success(array('templates' => $templates));
        error_log('KotacomAI: ajax_get_templates finished successfully.');
    }
    
    /**
     * Get all available templates
     */
    public function get_templates($type = '') {
        $args = array(
            'post_type' => 'kotacom_template',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        if (!empty($type)) {
            $args['meta_query'] = array(
                array(
                    'key' => 'template_type',
                    'value' => $type,
                    'compare' => '='
                )
            );
        }
        
        return get_posts($args);
    }
    
    /**
     * Apply template to content
     */
    public function apply_template($template_id, $keyword, $variables = array()) {
        $template = get_post($template_id);
        
        if (!$template) {
            return false;
        }
        
        $content = $template->post_content;
        
        // Set global keyword for shortcode processing context
        global $kotacom_ai_current_keyword;
        $kotacom_ai_current_keyword = $keyword;

        // Replace keyword
        $content = str_replace('{keyword}', $keyword, $content);
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        // Process shortcodes and generate AI content
        $final_content = do_shortcode($content);

        // Clear global keyword
        unset($kotacom_ai_current_keyword);
        
        return $final_content;
    }
}
