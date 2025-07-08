<?php
/**
 * Admin interface class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Admin {
    
    private $database;
    
    public function __construct() {
        $this->database = new KotacomAI_Database();
        $this->init();
    }
    
    /**
     * Initialize admin interface
     */
    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('post_row_actions', array($this, 'add_generate_image_row_action'), 10, 2);
        add_action('admin_footer-edit.php', array($this, 'hero_image_js')); // JS in post list
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Kotacom AI', 'kotacom-ai'),
            __('Kotacom AI', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai',
            array($this, 'display_generator_page'),
            'dashicons-robot',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'kotacom-ai',
            __('Content Generator', 'kotacom-ai'),
            __('Generator', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai',
            array($this, 'display_generator_page')
        );
        
        add_submenu_page(
            'kotacom-ai',
            __('Keywords Management', 'kotacom-ai'),
            __('Keywords', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-keywords',
            array($this, 'display_keywords_page')
        );
        
        add_submenu_page(
            'kotacom-ai',
            __('Prompt Templates', 'kotacom-ai'),
            __('Prompts', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-prompts',
            array($this, 'display_prompts_page')
        );

        // NEW: Template Editor Submenu
        add_submenu_page(
            'kotacom-ai',
            __('Template Editor', 'kotacom-ai'),
            __('Templates', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-templates', // Unique slug for the template editor page
            array($this, 'display_template_editor_page') // New method to display the page
        );
        
        add_submenu_page(
            'kotacom-ai',
            __('Queue Status', 'kotacom-ai'),
            __('Queue', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-queue',
            array($this, 'display_queue_page')
        );
        
        add_submenu_page(
            'kotacom-ai',
            __('Settings', 'kotacom-ai'),
            __('Settings', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-settings',
            array($this, 'display_settings_page')
        );
        
        // Content Refresh page
        add_submenu_page(
            'kotacom-ai',
            __('Content Refresh', 'kotacom-ai'),
            __('Refresh', 'kotacom-ai'),
            'edit_posts',
            'kotacom-ai-refresh',
            array($this, 'display_content_refresh_page')
        );
        
        // Logs
        add_submenu_page(
            'kotacom-ai',
            __('Logs', 'kotacom-ai'),
            __('Logs', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-logs',
            array($this, 'display_logs_page')
        );
        

    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only enqueue for Kotacom AI admin pages
        if (strpos($hook, 'kotacom-ai') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('jquery-ui-sortable'); // Required for drag & drop in template editor
        wp_enqueue_script('jquery-ui-droppable'); // Required for drag & drop in template editor
        
        wp_enqueue_script(
            'kotacom-ai-admin',
            KOTACOM_AI_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'jquery-ui-autocomplete'),
            KOTACOM_AI_VERSION,
            true
        );
        
        wp_enqueue_style(
            'kotacom-ai-admin',
            KOTACOM_AI_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            KOTACOM_AI_VERSION
        );
        
        // Localize script (common for all Kotacom AI pages)
        wp_localize_script('kotacom-ai-admin', 'kotacomAI', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kotacom_ai_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'kotacom-ai'),
                'processing' => __('Processing...', 'kotacom-ai'),
                'error' => __('An error occurred. Please try again.', 'kotacom-ai'),
                'success' => __('Operation completed successfully.', 'kotacom-ai'),
                'required_field' => __('This field is required.', 'kotacom-ai'),
                'validation_error' => __('Please fill in all required fields.', 'kotacom-ai'),
                'permission_error' => __('Permission denied.', 'kotacom-ai'),
                'server_error' => __('Server error occurred.', 'kotacom-ai'),
                'network_error' => __('Network error occurred.', 'kotacom-ai')
            ),
            'settingsUrl' => admin_url('admin.php?page=kotacom-ai-settings') // Pass settings URL for configure button
        ));

        // Enqueue template editor specific script only on its page
        if ($hook === 'kotacom-ai_page_kotacom-ai-templates') {
            wp_enqueue_script(
                'kotacom-ai-template-editor',
                KOTACOM_AI_PLUGIN_URL . 'admin/js/template-editor.js',
                array('jquery', 'jquery-ui-sortable', 'jquery-ui-droppable', 'kotacom-ai-admin'), // Ensure admin.js is loaded first
                KOTACOM_AI_VERSION,
                true
            );
        }
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // API Settings - Google AI
        register_setting('kotacom_ai_settings', 'kotacom_ai_api_provider');
        register_setting('kotacom_ai_settings', 'kotacom_ai_google_ai_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_google_ai_model');
        
        // API Settings - OpenAI
        register_setting('kotacom_ai_settings', 'kotacom_ai_openai_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_openai_model');
        
        // API Settings - Groq
        register_setting('kotacom_ai_settings', 'kotacom_ai_groq_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_groq_model');
        
        // API Settings - Cohere
        register_setting('kotacom_ai_settings', 'kotacom_ai_cohere_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_cohere_model');
        
        // API Settings - Hugging Face
        register_setting('kotacom_ai_settings', 'kotacom_ai_huggingface_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_huggingface_model');
        
        // API Settings - Together AI
        register_setting('kotacom_ai_settings', 'kotacom_ai_together_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_together_model');
        
        // API Settings - Anthropic
        register_setting('kotacom_ai_settings', 'kotacom_ai_anthropic_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_anthropic_model');
        
        // API Settings - Replicate
        register_setting('kotacom_ai_settings', 'kotacom_ai_replicate_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_replicate_model');
        
        // Image Provider Settings
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_image_provider');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_image_size');
        register_setting('kotacom_ai_settings', 'kotacom_ai_auto_featured_image');
        register_setting('kotacom_ai_settings', 'kotacom_ai_unsplash_access_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_pixabay_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_pexels_api_key');
        
        // Default Parameters
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_tone');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_length');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_audience');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_post_type');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_post_status');
        
        // Queue Settings
        register_setting('kotacom_ai_settings', 'kotacom_ai_queue_batch_size');
        register_setting('kotacom_ai_settings', 'kotacom_ai_queue_processing_interval');

        // Internal Linking Settings
        register_setting('kotacom_ai_settings', 'kotacom_ai_internal_link_enable');
        register_setting('kotacom_ai_settings', 'kotacom_ai_internal_link_max');
        register_setting('kotacom_ai_settings', 'kotacom_ai_internal_link_rule');
        register_setting('kotacom_ai_settings', 'kotacom_ai_internal_link_anchor_style');
        register_setting('kotacom_ai_settings', 'kotacom_ai_internal_link_dict');

        // Multi Provider list
        register_setting('kotacom_ai_settings', 'kotacom_ai_providers');
    }

    /**
     * Run database migrations if needed
     */
    private function run_migrations() {
        $current_version = get_option('kotacom_ai_version', '1.0.0');
        $new_version = KOTACOM_AI_VERSION;

        if (version_compare($current_version, $new_version, '<')) {
            // Add migration logic here if needed
            // For example, if you need to add a new option or update an existing one
            // update_option('kotacom_ai_version', $new_version);
        }
    }
    
    /**
     * Display inline help box
     */
    private function display_help_box($title, $content, $type = 'info') {
        $class = 'help-box help-' . $type;
        echo '<div class="' . $class . '">';
        echo '<h4><span class="dashicons dashicons-info"></span> ' . esc_html($title) . '</h4>';
        echo '<div class="help-content">' . $content . '</div>';
        echo '</div>';
    }

    /**
     * Display quick tips box
     */
    private function display_tips_box($tips) {
        echo '<div class="tips-box">';
        echo '<h4><span class="dashicons dashicons-lightbulb"></span> ' . __('Quick Tips', 'kotacom-ai') . '</h4>';
        echo '<ul>';
        foreach ($tips as $tip) {
            echo '<li>' . $tip . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }

    /**
     * Display shortcode examples box
     */
    private function display_shortcode_examples($shortcodes) {
        echo '<div class="shortcode-examples">';
        echo '<h4><span class="dashicons dashicons-editor-code"></span> ' . __('Available Shortcodes', 'kotacom-ai') . '</h4>';
        foreach ($shortcodes as $shortcode => $example) {
            echo '<div class="shortcode-item">';
            echo '<strong>' . esc_html($shortcode) . '</strong>';
            echo '<code>' . esc_html($example) . '</code>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Display content generator page
     */
    public function display_generator_page() {
        $prompts = $this->database->get_prompts();
        $tags = $this->database->get_all_tags();
        $categories = get_categories(array('hide_empty' => false));
        $post_types = get_post_types(array('public' => true), 'objects');
        
        // Add help information
        $this->add_generator_help_info();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/generator.php';
    }

    /**
     * Add help information for generator page
     */
    private function add_generator_help_info() {
        add_action('admin_notices', function() {
            $this->display_help_box(
                __('Content Generator', 'kotacom-ai'),
                '<p>' . __('Generate high-quality content using AI. Fill in the keyword and customize settings as needed.', 'kotacom-ai') . '</p>' .
                '<p><strong>' . __('Pro Tip:', 'kotacom-ai') . '</strong> ' . __('Use specific keywords for better results. Example: "WordPress SEO tips" instead of just "SEO"', 'kotacom-ai') . '</p>'
            );
            
            $tips = array(
                __('Start with specific, long-tail keywords for better content', 'kotacom-ai'),
                __('Choose the right tone based on your audience', 'kotacom-ai'),
                __('Longer content typically ranks better in search engines', 'kotacom-ai'),
                __('Review and edit AI-generated content before publishing', 'kotacom-ai')
            );
            $this->display_tips_box($tips);
            
            $shortcodes = array(
                '[ai_content]' => '[ai_content keyword="your keyword" tone="professional" length="long"]',
                '[ai_image]' => '[ai_image prompt="beautiful landscape" size="1200x800" provider="unsplash"]',
                '[ai_title]' => '[ai_title keyword="your keyword" style="catchy"]',
                '[ai_excerpt]' => '[ai_excerpt content="your content here" length="short"]',
                '[ai_meta]' => '[ai_meta keyword="your keyword" type="description"]'
            );
            $this->display_shortcode_examples($shortcodes);
        });
    }
    
    /**
     * Display keywords management page
     */
    public function display_keywords_page() {
        $tags = $this->database->get_all_tags();
        
        // Add help information
        $this->add_keywords_help_info();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/keywords.php';
    }

    /**
     * Add help information for keywords page
     */
    private function add_keywords_help_info() {
        add_action('admin_notices', function() {
            $this->display_help_box(
                __('Keywords Management', 'kotacom-ai'),
                '<p>' . __('Manage your keyword lists for bulk content generation. Organize keywords by tags for better workflow.', 'kotacom-ai') . '</p>' .
                '<p><strong>' . __('How it works:', 'kotacom-ai') . '</strong> ' . __('Add keywords here, then use them in bulk generation or templates.', 'kotacom-ai') . '</p>'
            );
            
            $tips = array(
                __('Group related keywords using tags (e.g., "Tech", "Health", "Finance")', 'kotacom-ai'),
                __('Use both short and long-tail keywords for variety', 'kotacom-ai'),
                __('Research keywords using tools like Google Keyword Planner', 'kotacom-ai'),
                __('Update your keyword list regularly based on trends', 'kotacom-ai')
            );
            $this->display_tips_box($tips);
        });
    }
    
    /**
     * Display prompts management page
     */
    public function display_prompts_page() {
        // Add help information
        $this->add_prompts_help_info();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/prompts.php';
    }

    /**
     * Add help information for prompts page
     */
    private function add_prompts_help_info() {
        add_action('admin_notices', function() {
            $this->display_help_box(
                __('Prompt Templates', 'kotacom-ai'),
                '<p>' . __('Create reusable prompt templates for consistent AI content generation. Use variables like {keyword}, {tone}, {audience}.', 'kotacom-ai') . '</p>' .
                '<p><strong>' . __('Variables available:', 'kotacom-ai') . '</strong> {keyword}, {tone}, {length}, {audience}, {language}, {style}</p>'
            );
            
            $tips = array(
                __('Be specific in your prompts for better AI responses', 'kotacom-ai'),
                __('Use variables to make templates reusable', 'kotacom-ai'),
                __('Test different prompt styles to find what works best', 'kotacom-ai'),
                __('Include context and instructions in your prompts', 'kotacom-ai')
            );
            $this->display_tips_box($tips);
        });
    }

    /**
     * NEW: Display template editor page
     */
    public function display_template_editor_page() {
        // Add help information
        $this->add_template_editor_help_info();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/template-editor.php';
    }

    /**
     * Add help information for template editor page
     */
    private function add_template_editor_help_info() {
        add_action('admin_notices', function() {
            $this->display_help_box(
                __('Template Editor', 'kotacom-ai'),
                '<p>' . __('Create and customize content templates with drag-and-drop components. Templates help maintain consistent content structure.', 'kotacom-ai') . '</p>' .
                '<p><strong>' . __('How to use:', 'kotacom-ai') . '</strong> ' . __('Drag components from the left panel to build your template structure.', 'kotacom-ai') . '</p>'
            );
            
            $tips = array(
                __('Start with a basic structure: Title → Introduction → Body → Conclusion', 'kotacom-ai'),
                __('Use image components to automatically generate relevant images', 'kotacom-ai'),
                __('Save templates for reuse across multiple content pieces', 'kotacom-ai'),
                __('Test templates with different keywords to ensure flexibility', 'kotacom-ai')
            );
            $this->display_tips_box($tips);
        });
    }
    
    /**
     * Display queue status page
     */
    public function display_queue_page() {
        $queue_manager = new KotacomAI_Queue_Manager();
        $queue_status = $queue_manager->get_queue_status();
        $failed_items = $queue_manager->get_failed_items();
        
        // Add help information
        $this->add_queue_help_info();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/queue.php';
    }

    /**
     * Add help information for queue page
     */
    private function add_queue_help_info() {
        add_action('admin_notices', function() {
            $this->display_help_box(
                __('Queue Status', 'kotacom-ai'),
                '<p>' . __('Monitor content generation progress and manage failed items. The queue processes items automatically in the background.', 'kotacom-ai') . '</p>' .
                '<p><strong>' . __('Status meanings:', 'kotacom-ai') . '</strong> Pending (waiting), Processing (generating), Completed (done), Failed (error occurred)</p>'
            );
            
            $tips = array(
                __('Failed items usually indicate API key issues or rate limits', 'kotacom-ai'),
                __('Check Settings page if many items are failing', 'kotacom-ai'),
                __('Queue processes automatically every few minutes', 'kotacom-ai'),
                __('Large batches may take longer to complete', 'kotacom-ai')
            );
            $this->display_tips_box($tips);
        });
    }
    
    /**
     * Display settings page
     */
    public function display_settings_page() {
        $api_handler = new KotacomAI_API_Handler();
        $providers = $api_handler->get_providers();
        
        // Add help information
        $this->add_settings_help_info();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Add help information for settings page
     */
    private function add_settings_help_info() {
        add_action('admin_notices', function() {
            $this->display_help_box(
                __('Plugin Settings', 'kotacom-ai'),
                '<p>' . __('Configure API keys and default settings. You need at least one AI provider configured to generate content.', 'kotacom-ai') . '</p>' .
                '<p><strong>' . __('Recommended:', 'kotacom-ai') . '</strong> ' . __('Start with Google AI (free tier available) or OpenAI for best results.', 'kotacom-ai') . '</p>'
            );
            
            $tips = array(
                __('Test API connections after entering keys', 'kotacom-ai'),
                __('Most image providers offer free tiers - no API key needed for Lorem Picsum', 'kotacom-ai'),
                __('Set reasonable default values to save time', 'kotacom-ai'),
                __('Keep API keys secure and never share them', 'kotacom-ai')
            );
            $this->display_tips_box($tips);
        });
    }

    /**
     * Add help information for content refresh page
     */
    private function add_content_refresh_help_info() {
        add_action('admin_notices', function() {
            $this->display_help_box(
                __('Content Refresh with AI', 'kotacom-ai'),
                '<p>' . __('Update existing posts with fresh AI-generated content using smart placeholders and templates.', 'kotacom-ai') . '</p>' .
                '<p><strong>' . __('How it works:', 'kotacom-ai') . '</strong> ' . __('AI reads your existing content and creates new/updated content based on your instructions. Use placeholders like {title} and {current_content} to reference existing post data.', 'kotacom-ai') . '</p>' .
                '<p><strong>' . __('Two methods:', 'kotacom-ai') . '</strong> ' . __('1) Write custom prompts with placeholders, or 2) Use pre-made templates that automatically insert post data.', 'kotacom-ai') . '</p>'
            );
            
            $tips = array(
                __('Use {title} to reference the post title in your instructions', 'kotacom-ai'),
                __('Use {current_content} to have AI work with existing content', 'kotacom-ai'),
                __('Templates automatically replace {keyword} with the post title', 'kotacom-ai'),
                __('Filter posts by date/category to target specific content', 'kotacom-ai'),
                __('Always backup important content before refreshing', 'kotacom-ai'),
                __('Review AI-generated updates before publishing', 'kotacom-ai')
            );
            $this->display_tips_box($tips);
        });
    }

    /**
     * Add help information for logs page
     */
    private function add_logs_help_info() {
        add_action('admin_notices', function() {
            $this->display_help_box(
                __('Activity Logs', 'kotacom-ai'),
                '<p>' . __('Track all plugin activities and troubleshoot issues. View success rates and filter by action type.', 'kotacom-ai') . '</p>' .
                '<p><strong>' . __('Color coding:', 'kotacom-ai') . '</strong> ' . __('Green = Success, Red = Error, Yellow = Warning, Blue = Info', 'kotacom-ai') . '</p>'
            );
            
            $tips = array(
                __('Check logs if content generation is failing', 'kotacom-ai'),
                __('High error rates usually indicate API issues', 'kotacom-ai'),
                __('Clear old logs periodically to maintain performance', 'kotacom-ai'),
                __('Export logs for technical support if needed', 'kotacom-ai')
            );
            $this->display_tips_box($tips);
        });
    }



    /** Row action */
    public function add_generate_image_row_action($actions, $post) {
        if ($post->post_type === 'post') {
            $nonce = wp_create_nonce('kotacom_ai_nonce');
            $actions['generate_ai_image'] = '<a href="#" class="generate-hero-image" data-post-id="' . $post->ID . '" data-nonce="' . $nonce . '">' . __('Generate Hero Image', 'kotacom-ai') . '</a>';
        }
        return $actions;
    }

    public function hero_image_js() {
        $screen = get_current_screen();
        if ($screen->id !== 'edit-post') return;
        ?>
        <script>
        jQuery(function($){
            $(document).on('click', '.generate-hero-image', function(e){
                e.preventDefault();
                var $link = $(this);
                if($link.hasClass('busy')) return;
                var postId = $link.data('post-id');
                var nonce  = $link.data('nonce');
                var prompt = 'High quality hero image for: ' + $link.closest('tr').find('.row-title').text();
                $link.addClass('busy').text('Generating...');
                $.post(ajaxurl, {
                    action: 'kotacom_generate_image',
                    nonce: nonce,
                    prompt: prompt,
                    post_id: postId,
                    featured: 'yes',
                    provider: 'unsplash'
                }, function(res){
                    if(res.success){
                        alert('Hero image set!');
                    }else{
                        alert('Error: '+res.data.message);
                    }
                    $link.removeClass('busy').text('Generate Hero Image');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Content Refresh admin page
     */
    public function display_content_refresh_page() {
        if (!current_user_can('edit_posts')) return;
        
        // Add help information
        $this->add_content_refresh_help_info();
        
        // Provider selector variables
        $api_handler = new KotacomAI_API_Handler();
        $providers = $api_handler->get_providers();
        $current_global_provider = get_option('kotacom_ai_api_provider', 'google_ai');

        // Enhanced post query with pagination support
        $posts_per_page = isset($_GET['posts_per_page']) ? max(10, min(200, intval($_GET['posts_per_page']))) : 25;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $date_filter = isset($_GET['date_filter']) ? sanitize_text_field($_GET['date_filter']) : 'all';
        $category_filter = isset($_GET['category_filter']) ? intval($_GET['category_filter']) : 0;
        $search_term = isset($_GET['search_posts']) ? sanitize_text_field($_GET['search_posts']) : '';
        
        $args = array(
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'post_status' => 'publish',
            'post_type' => 'post',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Add search functionality
        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }
        
        // Add category filter
        if ($category_filter > 0) {
            $args['cat'] = $category_filter;
        }
        
        // Add date filtering
        if ($date_filter !== 'all') {
            switch ($date_filter) {
                case 'last_week':
                    $args['date_query'] = array(
                        array(
                            'after' => '1 week ago'
                        )
                    );
                    break;
                case 'last_month':
                    $args['date_query'] = array(
                        array(
                            'after' => '1 month ago'
                        )
                    );
                    break;
                case 'last_3_months':
                    $args['date_query'] = array(
                        array(
                            'after' => '3 months ago'
                        )
                    );
                    break;
                case 'last_year':
                    $args['date_query'] = array(
                        array(
                            'after' => '1 year ago'
                        )
                    );
                    break;
                case 'older_than_year':
                    $args['date_query'] = array(
                        array(
                            'before' => '1 year ago'
                        )
                    );
                    break;
            }
        }
        
        $posts_query = new WP_Query($args);
        $posts = $posts_query->posts;
        $total_posts = $posts_query->found_posts;
        $max_pages = $posts_query->max_num_pages;
        
        $nonce = wp_create_nonce('kotacom_ai_nonce');
        ?>
        <div class="wrap">
            <h1><?php _e('Content Refresh', 'kotacom-ai'); ?></h1>
            <p><?php _e('Select posts, enter a refresh prompt, and let AI update the content.', 'kotacom-ai'); ?></p>
            
            <!-- Usage Guide -->
            <div class="notice notice-info" style="margin-bottom: 20px;">
                <h4 style="margin-top: 10px;">📋 <?php _e('How to Use Content Refresh', 'kotacom-ai'); ?></h4>
                <div style="display: flex; gap: 30px; margin: 15px 0;">
                    <div style="flex: 1;">
                        <h5>🎯 <?php _e('Available Placeholders:', 'kotacom-ai'); ?></h5>
                        <ul style="margin: 5px 0 0 20px; font-family: monospace; font-size: 12px;">
                            <li><code>{title}</code> - <?php _e('Post title', 'kotacom-ai'); ?></li>
                            <li><code>{current_content}</code> - <?php _e('Existing content', 'kotacom-ai'); ?></li>
                            <li><code>{excerpt}</code> - <?php _e('Post excerpt', 'kotacom-ai'); ?></li>
                            <li><code>{categories}</code> - <?php _e('Post categories', 'kotacom-ai'); ?></li>
                            <li><code>{tags}</code> - <?php _e('Post tags', 'kotacom-ai'); ?></li>
                        </ul>
                    </div>
                    <div style="flex: 1;">
                        <h5>💡 <?php _e('Example Prompts:', 'kotacom-ai'); ?></h5>
                        <ul style="margin: 5px 0 0 20px; font-size: 12px;">
                            <li><em>"Update {title} with 2025 statistics and add FAQ section"</em></li>
                            <li><em>"Rewrite the introduction of {current_content} to be more engaging"</em></li>
                            <li><em>"Add conclusion section to {title} about {categories}"</em></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Filters -->
            <div class="postbox" style="margin-bottom: 20px;">
                <h3 class="hndle" style="padding: 10px 15px;"><?php _e('🔍 Filter Posts', 'kotacom-ai'); ?></h3>
                <div class="inside" style="padding: 15px;">
                    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                        <!-- Search Posts -->
                        <div>
                            <label for="search-posts" style="font-weight: bold;"><?php _e('Search:', 'kotacom-ai'); ?></label>
                            <input type="text" id="search-posts" placeholder="<?php _e('Search posts...', 'kotacom-ai'); ?>" 
                                   value="<?php echo esc_attr($search_term); ?>" style="width: 200px;">
                        </div>
                        
                        <!-- Category Filter -->
                        <div>
                            <label for="category-filter" style="font-weight: bold;"><?php _e('Category:', 'kotacom-ai'); ?></label>
                            <select id="category-filter">
                                <option value="0"><?php _e('All Categories', 'kotacom-ai'); ?></option>
                                <?php $all_cats = get_categories(array('hide_empty'=>false));
                                foreach($all_cats as $c): ?>
                                    <option value="<?php echo esc_attr($c->term_id); ?>" <?php selected($category_filter, $c->term_id); ?>>
                                        <?php echo esc_html($c->name); ?> (<?php echo $c->count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Date Filter -->
                        <div>
                            <label for="date-filter" style="font-weight: bold;"><?php _e('Date:', 'kotacom-ai'); ?></label>
                            <select id="date-filter">
                                <option value="all" <?php selected($date_filter, 'all'); ?>><?php _e('All Dates', 'kotacom-ai'); ?></option>
                                <option value="last_week" <?php selected($date_filter, 'last_week'); ?>><?php _e('Last Week', 'kotacom-ai'); ?></option>
                                <option value="last_month" <?php selected($date_filter, 'last_month'); ?>><?php _e('Last Month', 'kotacom-ai'); ?></option>
                                <option value="last_3_months" <?php selected($date_filter, 'last_3_months'); ?>><?php _e('Last 3 Months', 'kotacom-ai'); ?></option>
                                <option value="last_year" <?php selected($date_filter, 'last_year'); ?>><?php _e('Last Year', 'kotacom-ai'); ?></option>
                                <option value="older_than_year" <?php selected($date_filter, 'older_than_year'); ?>><?php _e('Older than 1 Year', 'kotacom-ai'); ?></option>
                            </select>
                        </div>
                        
                        <!-- Posts Per Page -->
                        <div>
                            <label for="posts-per-page" style="font-weight: bold;"><?php _e('Show:', 'kotacom-ai'); ?></label>
                            <select id="posts-per-page">
                                <option value="10" <?php selected($posts_per_page, 10); ?>>10</option>
                                <option value="25" <?php selected($posts_per_page, 25); ?>>25</option>
                                <option value="50" <?php selected($posts_per_page, 50); ?>>50</option>
                                <option value="100" <?php selected($posts_per_page, 100); ?>>100</option>
                            </select>
                        </div>
                        
                        <button type="button" id="apply-filters" class="button button-secondary"><?php _e('Apply Filters', 'kotacom-ai'); ?></button>
                    </div>
                </div>
            </div>
            
            <!-- AI Provider Selection (optional override) -->
            <div class="postbox">
                <h3 class="hndle" style="padding:10px 15px;">
                    <?php _e('⚙️ AI Provider Selection (Optional Override)', 'kotacom-ai'); ?>
                </h3>
                <div class="inside" style="padding:15px;">
                    <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
                        <div>
                            <label for="session-provider"><strong><?php _e('Provider:', 'kotacom-ai'); ?></strong></label>
                            <select id="session-provider" name="session_provider" style="min-width:200px;">
                                <option value=""><?php _e('Use Global Setting', 'kotacom-ai'); ?> (<?php echo esc_html($providers[$current_global_provider]['name'] ?? 'N/A'); ?>)</option>
                                <?php foreach ($providers as $key => $provider): ?>
                                    <option value="<?php echo esc_attr($key); ?>" data-models="<?php echo esc_attr(json_encode($api_handler->get_provider_models($key))); ?>">
                                        <?php echo esc_html($provider['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="model-select-wrapper" style="display:none;">
                            <label for="session-model"><strong><?php _e('Model:', 'kotacom-ai'); ?></strong></label>
                            <select id="session-model" name="session_model" style="min-width:180px;"></select>
                        </div>
                        <button type="button" id="test-provider-connection" class="button button-secondary" style="display:none;">
                            <?php _e('Test Connection', 'kotacom-ai'); ?>
                        </button>
                    </div>
                    <p class="description" style="margin-top:8px;"><?php _e('If selected, this provider/model will be used for the refresh operation instead of the global default.', 'kotacom-ai'); ?></p>
                </div>
            </div>

            <!-- Queue Debug & Logs Panel -->
            <div style="margin: 20px 0; padding: 10px; background:#f9f9f9;border:1px solid #ddd;border-radius:5px;">
                <h4 style="margin:0 0 10px 0;"><?php _e('🔧 Queue Debug & Logs', 'kotacom-ai'); ?></h4>
                <button type="button" id="check-queue-status" class="button"><?php _e('Check Queue Status', 'kotacom-ai'); ?></button>
                <button type="button" id="process-queue-manually" class="button button-secondary"><?php _e('Process Queue Now', 'kotacom-ai'); ?></button>
                <button type="button" id="clear-failed-queue" class="button button-primary" style="background:#dc3232;"><?php _e('Clear Failed Items', 'kotacom-ai'); ?></button>
                <div id="queue-status-display" style="margin-top:10px;font-family:monospace;font-size:12px;background:white;padding:10px;border-radius:3px;max-height:200px;overflow-y:auto;display:none;"></div>
            </div>
            
            <!-- Template and Prompt Selection -->
            <div class="postbox">
                <h3 class="hndle" style="padding: 10px 15px;"><?php _e('🤖 Refresh Instructions', 'kotacom-ai'); ?></h3>
                <div class="inside" style="padding: 15px;">
                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <label for="refresh-template" style="font-weight: bold;"><?php _e('Use Template:', 'kotacom-ai'); ?></label>
                            <select id="refresh-template" style="width: 100%;">
                                <option value=""><?php _e('— Or select a template —', 'kotacom-ai'); ?></option>
                                <?php
                                $templates = get_posts(array('post_type' => 'kotacom_template', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                                foreach($templates as $t): ?>
                                    <option value="<?php echo esc_attr($t->ID); ?>"><?php echo esc_html($t->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-weight: bold; display: block; margin-bottom: 5px;"><?php _e('Options:', 'kotacom-ai'); ?></label>
                            <label>
                                <input type="checkbox" id="update-date" /> 
                                <?php _e('Update post date to now', 'kotacom-ai'); ?>
                            </label>
                        </div>
                    </div>
                    
                    <label for="refresh-prompt" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php _e('Custom Refresh Prompt:', 'kotacom-ai'); ?></label>
                    <textarea id="refresh-prompt" style="width:100%; min-height:120px;" 
                              placeholder="<?php _e('Example: Update {title} with latest 2025 trends and add FAQ section. Use {current_content} as base and focus on {categories} topics.', 'kotacom-ai'); ?>"></textarea>
                    <p class="description"><?php _e('Use placeholders like {title}, {current_content}, {categories} etc. Templates can be used instead of writing custom prompts.', 'kotacom-ai'); ?></p>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
                <div>
                    <p style="margin: 0;"><strong><?php _e('Total Posts:', 'kotacom-ai'); ?></strong> <?php echo number_format($total_posts); ?> | 
                       <strong><?php _e('Showing:', 'kotacom-ai'); ?></strong> <?php echo count($posts); ?> <?php _e('posts on this page', 'kotacom-ai'); ?></p>
                </div>
                <div>
                    <label>
                        <input type="checkbox" id="select-all" /> 
                        <strong><?php _e('Select All on This Page', 'kotacom-ai'); ?></strong>
                    </label>
                </div>
            </div>
            <table class="widefat fixed striped" id="refresh-table">
                <thead><tr><th style="width: 50px;"></th><th><?php _e('Title', 'kotacom-ai'); ?></th><th style="width: 120px;"><?php _e('Date', 'kotacom-ai'); ?></th><th style="width: 120px;"><?php _e('Categories', 'kotacom-ai'); ?></th></tr></thead>
                <tbody>
                <?php foreach($posts as $p): 
                    $cats = wp_get_post_categories($p->ID);
                    $cat_names = array();
                    foreach($cats as $cat_id) {
                        $cat = get_category($cat_id);
                        if ($cat) {
                            $cat_names[] = $cat->name;
                        }
                    }
                ?>
                    <tr data-cats="<?php echo esc_attr(implode(',', $cats)); ?>">
                        <td><input type="checkbox" class="post-select" value="<?php echo esc_attr($p->ID); ?>" /></td>
                        <td>
                            <strong><?php echo esc_html($p->post_title); ?></strong>
                            <div style="margin-top: 5px;">
                                <a href="<?php echo get_edit_post_link($p->ID); ?>" target="_blank" style="font-size: 12px; color: #666; text-decoration: none;">
                                    <?php _e('Edit', 'kotacom-ai'); ?> →
                                </a>
                                <span style="margin: 0 5px; color: #ddd;">|</span>
                                <a href="<?php echo get_permalink($p->ID); ?>" target="_blank" style="font-size: 12px; color: #666; text-decoration: none;">
                                    <?php _e('View', 'kotacom-ai'); ?> →
                                </a>
                            </div>
                        </td>
                        <td style="font-size: 12px; color: #666;">
                            <?php echo esc_html(get_the_date('M j, Y', $p)); ?>
                            <br><small><?php echo esc_html(get_the_date('H:i', $p)); ?></small>
                        </td>
                        <td style="font-size: 12px;">
                            <?php if (!empty($cat_names)): ?>
                                <?php foreach($cat_names as $cat_name): ?>
                                    <span style="display: inline-block; background: #f0f0f1; padding: 2px 6px; margin: 1px; border-radius: 3px; font-size: 11px;">
                                        <?php echo esc_html($cat_name); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: #999; font-style: italic;"><?php _e('Uncategorized', 'kotacom-ai'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button class="button button-primary" id="run-refresh" data-nonce="<?php echo esc_attr($nonce); ?>"><?php _e('Run Refresh', 'kotacom-ai'); ?></button>
                <span id="refresh-progress" style="margin-left:15px;"></span>
            </p>
            
            <!-- Pagination -->
            <?php if ($max_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php printf(__('%s items', 'kotacom-ai'), number_format($total_posts)); ?></span>
                    <?php
                    $page_links = paginate_links(array(
                        'base' => add_query_arg(array('paged' => '%#%', 'date_filter' => $date_filter)),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $max_pages,
                        'current' => $paged
                    ));
                    if ($page_links) {
                        echo '<span class="pagination-links">' . $page_links . '</span>';
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <script>
        jQuery(function($){
            // Select all functionality
            $('#select-all').on('change', function(){
                $('.post-select').prop('checked', $(this).is(':checked'));
            });

            // Apply filters functionality
            function applyFilters() {
                var searchTerm = $('#search-posts').val();
                var categoryFilter = $('#category-filter').val();
                var dateFilter = $('#date-filter').val();
                var postsPerPage = $('#posts-per-page').val();
                
                var url = new URL(window.location.href);
                url.searchParams.set('search_posts', searchTerm);
                url.searchParams.set('category_filter', categoryFilter);
                url.searchParams.set('date_filter', dateFilter);
                url.searchParams.set('posts_per_page', postsPerPage);
                url.searchParams.delete('paged'); // Reset to first page
                
                window.location.href = url.toString();
            }
            
            $('#apply-filters').on('click', applyFilters);
            
            // Allow Enter key in search to apply filters
            $('#search-posts').on('keypress', function(e){
                if(e.which === 13) {
                    applyFilters();
                }
            });

            // Load template content into textarea
            $('#refresh-template').on('change', function(){
                var tid = $(this).val();
                if(!tid){ 
                    $('#refresh-prompt').val('');
                    $('#refresh-prompt').attr('placeholder', '<?php echo esc_js(__('Example: Update {title} with latest 2025 trends and add FAQ section. Use {current_content} as base and focus on {categories} topics.', 'kotacom-ai')); ?>');
                    return; 
                }
                
                $.post(ajaxurl, { 
                    action: 'kotacom_get_template', 
                    nonce: '<?php echo esc_js($nonce); ?>', 
                    template_id: tid 
                }, function(res){
                    if(res.success && res.data && res.data.template){
                        $('#refresh-prompt').val(res.data.template.content);
                        $('#refresh-prompt').attr('placeholder', '<?php echo esc_js(__('Template loaded. You can edit it or use as-is.', 'kotacom-ai')); ?>');
                    }
                }).fail(function(){
                    alert('<?php echo esc_js(__('Failed to load template. Please try again.', 'kotacom-ai')); ?>');
                });
            });

            $('#run-refresh').on('click', function(){
                var ids = $('.post-select:checked').map(function(){return $(this).val();}).get();
                if(ids.length === 0){alert('Select at least one post');return;}
                var prompt = $('#refresh-prompt').val();
                if(!prompt){alert('Enter refresh prompt');return;}
                var nonce = $(this).data('nonce');
                var templateId = $('#refresh-template').val();
                var updateDate = $('#update-date').is(':checked') ? 'yes' : 'no';
                $(this).prop('disabled', true).text('Processing…');
                var batchSize = 20;
                var batches = [];
                for(var i=0;i<ids.length;i+=batchSize){ batches.push(ids.slice(i,i+batchSize)); }

                var completed = 0;
                $('#refresh-progress').text('0 / '+ids.length);

                function processBatch(idx){
                    if(idx>=batches.length){
                        $('#run-refresh').prop('disabled', false).text('Run Refresh');
                        alert('Refresh completed');
                        return;
                    }
                    $.post(ajaxurl, {
                        action: 'kotacom_refresh_posts',
                        nonce: nonce,
                        post_ids: batches[idx],
                        refresh_prompt: prompt,
                        template_id: templateId,
                        update_date: updateDate
                    }, function(res){
                        completed += batches[idx].length;
                        $('#refresh-progress').text(completed+' / '+ids.length);
                        processBatch(idx+1);
                    }).fail(function(){
                        completed += batches[idx].length;
                        processBatch(idx+1);
                    });
                }

                processBatch(0);
            });

            // Provider info registry (simple)
            const providerInfo = <?php echo json_encode($providers); ?>;

            // Populate models when provider changes
            $('#session-provider').on('change', function(){
                const key = $(this).val();
                if(!key){ $('#model-select-wrapper').hide(); return; }
                const modelsData = $(this).find('option:selected').data('models');
                let models = {};
                try { models = JSON.parse(modelsData); }catch(e){ models = {}; }
                const $modelSel = $('#session-model');
                $modelSel.empty();
                $.each(models, function(k,v){ $modelSel.append('<option value="'+k+'">'+v+'</option>'); });
                $('#model-select-wrapper').toggle(Object.keys(models).length>0);
                $('#test-provider-connection').show();
            });

            // Test connection
            $('#test-provider-connection').on('click', function(){
                const provider = $('#session-provider').val();
                if(!provider) return;
                const $btn=$(this).prop('disabled',true).text('...');
                $.post(ajaxurl,{action:'kotacom_test_provider_connection',nonce:'<?php echo esc_js($nonce); ?>',provider:provider},function(res){
                    alert(res.success? '✔ OK':'✖ '+res.data.message);
                }).always(function(){ $btn.prop('disabled',false).text('<?php echo esc_js(__('Test Connection','kotacom-ai')); ?>'); });
            });

            // Queue debug (reuse existing JS from generator page)
            $('#check-queue-status').on('click', function(){
                var $btn=$(this),$display=$('#queue-status-display');
                $btn.prop('disabled',true).text('...');
                $.post(ajaxurl,{action:'kotacom_get_queue_debug',nonce:'<?php echo esc_js($nonce); ?>'},function(res){
                    if(res.success){ $display.text(JSON.stringify(res.data,null,2)); } else { $display.text(res.data.message); }
                    $display.show();
                }).always(()=>{$btn.prop('disabled',false).text('<?php echo esc_js(__('Check Queue Status','kotacom-ai')); ?>');});
            });
            $('#process-queue-manually').on('click', function(){
                $.post(ajaxurl,{action:'kotacom_process_queue_manually',nonce:'<?php echo esc_js($nonce); ?>'},function(res){ alert(res.success? res.data.message: res.data.message); });
            });
            $('#clear-failed-queue').on('click', function(){ if(!confirm('Clear failed items?')) return; $.post(ajaxurl,{action:'kotacom_clear_failed_queue',nonce:'<?php echo esc_js($nonce); ?>'},function(res){ alert(res.success? res.data.message: res.data.message); }); });
        });
        </script>
        <?php
    }

    /**
     * Enhanced Logs page with better filtering and display
     */
    public function display_logs_page() {
        if (!current_user_can('manage_options')) return;
        
        // Add help information
        $this->add_logs_help_info();
        
        // Handle clear logs action
        if (isset($_GET['action']) && $_GET['action'] === 'clear' && wp_verify_nonce($_GET['_wpnonce'], 'clear_logs')) {
            KotacomAI_Logger::clear_all_logs();
            wp_redirect(admin_url('admin.php?page=kotacom-ai-logs&cleared=1'));
            exit;
        }
        
        $filter = isset($_GET['result']) ? sanitize_text_field($_GET['result']) : '';
        $action_filter = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $limit = isset($_GET['limit']) ? max(10, min(500, intval($_GET['limit']))) : 100;
        
        $logs = KotacomAI_Logger::get_logs($limit, $filter, $action_filter);
        $stats = KotacomAI_Logger::get_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('Kotacom AI Logs', 'kotacom-ai'); ?></h1>
            
            <?php if (isset($_GET['cleared'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('All logs have been cleared successfully.', 'kotacom-ai'); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Dashboard -->
            <div class="log-stats" style="display: flex; gap: 20px; margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px;">
                <div class="stat-box" style="text-align: center;">
                    <h3 style="margin: 0; color: #28a745;"><?php echo number_format($stats['total_success']); ?></h3>
                    <p style="margin: 5px 0; color: #666;"><?php _e('Successful', 'kotacom-ai'); ?></p>
                </div>
                <div class="stat-box" style="text-align: center;">
                    <h3 style="margin: 0; color: #dc3545;"><?php echo number_format($stats['total_failed']); ?></h3>
                    <p style="margin: 5px 0; color: #666;"><?php _e('Failed', 'kotacom-ai'); ?></p>
                </div>
                <div class="stat-box" style="text-align: center;">
                    <h3 style="margin: 0; color: #007cba;"><?php echo number_format($stats['total_logs']); ?></h3>
                    <p style="margin: 5px 0; color: #666;"><?php _e('Total Logs', 'kotacom-ai'); ?></p>
                </div>
                <div class="stat-box" style="text-align: center;">
                    <h3 style="margin: 0; color: #ffc107;"><?php echo number_format($stats['success_rate'], 1); ?>%</h3>
                    <p style="margin: 5px 0; color: #666;"><?php _e('Success Rate', 'kotacom-ai'); ?></p>
                </div>
            </div>
            
            <!-- Enhanced Filters -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <a href="<?php echo admin_url('admin.php?page=kotacom-ai-logs'); ?>" class="button <?php echo ($filter=='' && $action_filter=='') ? 'button-primary' : ''; ?>"><?php _e('All', 'kotacom-ai'); ?> (<?php echo $stats['total_logs']; ?>)</a>
                    <a href="<?php echo admin_url('admin.php?page=kotacom-ai-logs&result=success'); ?>" class="button <?php echo $filter=='success' ? 'button-primary' : ''; ?>" style="color: #28a745;"><?php _e('Success', 'kotacom-ai'); ?> (<?php echo $stats['total_success']; ?>)</a>
                    <a href="<?php echo admin_url('admin.php?page=kotacom-ai-logs&result=fail'); ?>" class="button <?php echo $filter=='fail' ? 'button-primary' : ''; ?>" style="color: #dc3545;"><?php _e('Failed', 'kotacom-ai'); ?> (<?php echo $stats['total_failed']; ?>)</a>
                    
                    <select onchange="window.location.href=this.value;" style="margin-left: 10px;">
                        <option value="<?php echo admin_url('admin.php?page=kotacom-ai-logs'); ?>"><?php _e('All Actions', 'kotacom-ai'); ?></option>
                        <?php foreach ($stats['actions'] as $action => $count): ?>
                            <option value="<?php echo admin_url('admin.php?page=kotacom-ai-logs&action=' . urlencode($action)); ?>" <?php selected($action_filter, $action); ?>>
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $action))); ?> (<?php echo $count; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select onchange="window.location.href=this.value;" style="margin-left: 10px;">
                        <option value="<?php echo add_query_arg(['limit' => 50], admin_url('admin.php?page=kotacom-ai-logs')); ?>" <?php selected($limit, 50); ?>>50 <?php _e('entries', 'kotacom-ai'); ?></option>
                        <option value="<?php echo add_query_arg(['limit' => 100], admin_url('admin.php?page=kotacom-ai-logs')); ?>" <?php selected($limit, 100); ?>>100 <?php _e('entries', 'kotacom-ai'); ?></option>
                        <option value="<?php echo add_query_arg(['limit' => 200], admin_url('admin.php?page=kotacom-ai-logs')); ?>" <?php selected($limit, 200); ?>>200 <?php _e('entries', 'kotacom-ai'); ?></option>
                        <option value="<?php echo add_query_arg(['limit' => 500], admin_url('admin.php?page=kotacom-ai-logs')); ?>" <?php selected($limit, 500); ?>>500 <?php _e('entries', 'kotacom-ai'); ?></option>
                    </select>
                </div>
                
                <div class="alignright actions">
                    <button type="button" onclick="if(confirm('<?php _e('Are you sure you want to clear all logs?', 'kotacom-ai'); ?>')) location.href='<?php echo wp_nonce_url(admin_url('admin.php?page=kotacom-ai-logs&action=clear'), 'clear_logs'); ?>'" class="button button-secondary"><?php _e('Clear All Logs', 'kotacom-ai'); ?></button>
                </div>
            </div>
            
            <?php if (empty($logs)): ?>
            <div class="notice notice-info">
                <p><?php _e('No logs found matching your criteria.', 'kotacom-ai'); ?></p>
            </div>
            <?php else: ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 160px;"><?php _e('Date & Time', 'kotacom-ai'); ?></th>
                        <th style="width: 120px;"><?php _e('Action', 'kotacom-ai'); ?></th>
                        <th style="width: 80px;"><?php _e('Post', 'kotacom-ai'); ?></th>
                        <th style="width: 80px;"><?php _e('Status', 'kotacom-ai'); ?></th>
                        <th><?php _e('Message', 'kotacom-ai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($logs as $log): ?>
                    <tr class="<?php echo $log->success ? 'log-success' : 'log-failed'; ?>" style="<?php echo $log->success ? 'background-color: #f0f8f0;' : 'background-color: #fdf2f2;'; ?>">
                        <td>
                            <strong><?php echo date_i18n('M j, Y', strtotime($log->ts)); ?></strong><br>
                            <small style="color: #666;"><?php echo date_i18n('H:i:s', strtotime($log->ts)); ?></small>
                        </td>
                        <td>
                            <span class="action-badge" style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: #007cba; color: white;">
                                <?php echo esc_html(str_replace('_', ' ', $log->action)); ?>
                            </span>
                        </td>
                        <td>
                            <?php if($log->post_id): ?>
                                <a href="<?php echo get_edit_post_link($log->post_id); ?>" target="_blank" style="text-decoration: none;">
                                    <strong>#<?php echo $log->post_id; ?></strong>
                                </a>
                                <br><small style="color: #666;"><?php echo esc_html(get_the_title($log->post_id)); ?></small>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($log->success): ?>
                                <span style="font-size: 18px; color: #28a745;" title="<?php _e('Success', 'kotacom-ai'); ?>">✅</span>
                                <br><small style="color: #28a745; font-weight: bold;"><?php _e('SUCCESS', 'kotacom-ai'); ?></small>
                            <?php else: ?>
                                <span style="font-size: 18px; color: #dc3545;" title="<?php _e('Failed', 'kotacom-ai'); ?>">❌</span>
                                <br><small style="color: #dc3545; font-weight: bold;"><?php _e('FAILED', 'kotacom-ai'); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($log->message)): ?>
                                <div style="max-width: 300px; word-wrap: break-word;">
                                    <?php echo esc_html($log->message); ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #999; font-style: italic;"><?php _e('No message', 'kotacom-ai'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <p style="margin-top: 20px; color: #666; font-size: 13px;">
                <strong><?php _e('Note:', 'kotacom-ai'); ?></strong> <?php _e('Logs are automatically cleaned up after 30 days to maintain performance.', 'kotacom-ai'); ?>
            </p>
        </div>
        
        <style>
        .log-success { border-left: 4px solid #28a745; }
        .log-failed { border-left: 4px solid #dc3545; }
        .action-badge { white-space: nowrap; }
        .stat-box { flex: 1; }
        </style>
        <?php
    }
}
