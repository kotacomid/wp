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
        
        add_submenu_page(
            'kotacom-ai',
            __('Generator Post Template', 'kotacom-ai'),
            __('Generator Post Template', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-generator-post-template',
            array(
                $this,
                'display_generator_post_template_page'
            )
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
        
        // Unsplash
        register_setting('kotacom_ai_settings', 'kotacom_ai_unsplash_access_key');
        
        // Default Parameters
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_tone');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_length');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_audience');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_post_type');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_post_status');
        
        // Queue Settings
        register_setting('kotacom_ai_settings', 'kotacom_ai_queue_batch_size');
        register_setting('kotacom_ai_settings', 'kotacom_ai_queue_processing_interval');
    }
    
    /**
     * Display content generator page
     */
    public function display_generator_page() {
        $prompts = $this->database->get_prompts();
        $tags = $this->database->get_all_tags();
        $categories = get_categories(array('hide_empty' => false));
        $post_types = get_post_types(array('public' => true), 'objects');
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/generator.php';
    }
    
    /**
     * Display keywords management page
     */
    public function display_keywords_page() {
        $tags = $this->database->get_all_tags();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/keywords.php';
    }
    
    /**
     * Display prompts management page
     */
    public function display_prompts_page() {
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/prompts.php';
    }

    /**
     * NEW: Display template editor page
     */
    public function display_template_editor_page() {
        // You might need to pass data to the template editor view, e.g., existing templates
        // $templates = $this->database->get_templates(); // Assuming a method exists
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/template-editor.php';
    }
    
    /**
     * Display queue status page
     */
    public function display_queue_page() {
        $queue_status = $this->database->get_queue_status();
        $failed_items = $this->database->get_failed_queue_items();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/queue.php';
    }
    
    /**
     * Display settings page
     */
    public function display_settings_page() {
        $api_handler = new KotacomAI_API_Handler();
        $providers = $api_handler->get_providers();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Display Generator Post Template page
     */
    public function display_generator_post_template_page() {
        // Get data needed for the template
        $tags = $this->database->get_all_tags();
        $categories = get_categories(array('hide_empty' => false));
        $post_types = get_post_types(array('public' => true), 'objects');
        
        // Get custom post type templates (kotacom_template)
        $existing_templates = get_posts(array(
            'post_type' => 'kotacom_template',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // If no custom templates exist, create some default ones
        if (empty($existing_templates)) {
            $this->create_default_templates();
            $existing_templates = get_posts(array(
                'post_type' => 'kotacom_template',
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ));
        }
        
        include plugin_dir_path(__FILE__) . 'views/generator-post-template.php';
    }
    
    /**
     * Create default templates for the custom post type
     */
    private function create_default_templates() {
        $default_templates = array(
            array(
                'title' => 'Blog Article Template',
                'content' => '<h1>{keyword}</h1>

<p>Introduction about {keyword}...</p>

<h2>What is {keyword}?</h2>
<p>Detailed explanation of {keyword}...</p>

<h2>Benefits of {keyword}</h2>
<ul>
<li>Benefit 1</li>
<li>Benefit 2</li>
<li>Benefit 3</li>
</ul>

<h2>How to Use {keyword}</h2>
<p>Step-by-step guide...</p>

<h2>Conclusion</h2>
<p>Summary about {keyword}...</p>'
            ),
            array(
                'title' => 'Product Review Template',
                'content' => '<h1>{keyword} Review</h1>

<p>In this comprehensive review, we\'ll examine {keyword} in detail...</p>

<h2>Overview of {keyword}</h2>
<p>Brief overview...</p>

<h2>Pros and Cons</h2>
<h3>Pros:</h3>
<ul>
<li>Pro 1</li>
<li>Pro 2</li>
</ul>

<h3>Cons:</h3>
<ul>
<li>Con 1</li>
<li>Con 2</li>
</ul>

<h2>Final Verdict</h2>
<p>Our final thoughts on {keyword}...</p>'
            ),
            array(
                'title' => 'How-to Guide Template',
                'content' => '<h1>How to Use {keyword}: Complete Guide</h1>

<p>Learn everything you need to know about {keyword}...</p>

<h2>What You\'ll Need</h2>
<ul>
<li>Requirement 1</li>
<li>Requirement 2</li>
</ul>

<h2>Step-by-Step Instructions</h2>
<h3>Step 1: Getting Started</h3>
<p>First step instructions...</p>

<h3>Step 2: Implementation</h3>
<p>Second step instructions...</p>

<h3>Step 3: Finishing Up</h3>
<p>Final step instructions...</p>

<h2>Tips and Best Practices</h2>
<p>Additional tips for {keyword}...</p>'
            )
        );
        
        foreach ($default_templates as $template) {
            wp_insert_post(array(
                'post_title' => $template['title'],
                'post_content' => $template['content'],
                'post_status' => 'publish',
                'post_type' => 'kotacom_template',
                'post_author' => get_current_user_id()
            ));
        }
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
        $posts = get_posts(array('numberposts' => 20, 'post_status' => 'publish', 'post_type' => 'post', 'orderby' => 'date', 'order' => 'DESC'));
        $nonce = wp_create_nonce('kotacom_ai_nonce');
        ?>
        <div class="wrap">
            <h1><?php _e('Content Refresh', 'kotacom-ai'); ?></h1>
            <p><?php _e('Select posts, enter a refresh prompt, and let AI update the content.', 'kotacom-ai'); ?></p>
            <p>
                <label for="refresh-template"><strong><?php _e('Refresh Template:', 'kotacom-ai'); ?></strong></label>
                <select id="refresh-template">
                    <option value=""><?php _e('— Select template —', 'kotacom-ai'); ?></option>
                    <?php
                    $templates = get_posts(array('post_type' => 'kotacom_template', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                    foreach($templates as $t): ?>
                        <option value="<?php echo esc_attr($t->ID); ?>"><?php echo esc_html($t->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="checkbox" id="update-date" style="margin-left:15px;" /> <label for="update-date"><?php _e('Update post date to now', 'kotacom-ai'); ?></label>
            </p>
            <p>
                <label for="cat-filter"><strong><?php _e('Filter by Category:', 'kotacom-ai'); ?></strong></label>
                <select id="cat-filter">
                    <option value="all"><?php _e('All Categories', 'kotacom-ai'); ?></option>
                    <?php $all_cats = get_categories(array('hide_empty'=>false));
                    foreach($all_cats as $c): ?>
                        <option value="<?php echo esc_attr($c->term_id); ?>"><?php echo esc_html($c->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <textarea id="refresh-prompt" style="width:100%;min-height:120px;" placeholder="<?php _e('e.g., Rewrite intro, update stats to 2025, add FAQ… Use {current_content} and {title} placeholders.', 'kotacom-ai'); ?>"></textarea>
            <table class="widefat fixed striped" id="refresh-table">
                <thead><tr><th><input type="checkbox" id="select-all" /></th><th><?php _e('Title', 'kotacom-ai'); ?></th><th><?php _e('Date', 'kotacom-ai'); ?></th></tr></thead>
                <tbody>
                <?php foreach($posts as $p): $cats = wp_get_post_categories($p->ID); ?>
                    <tr data-cats="<?php echo esc_attr(implode(',', $cats)); ?>">
                        <td><input type="checkbox" class="post-select" value="<?php echo esc_attr($p->ID); ?>" /></td>
                        <td><?php echo esc_html($p->post_title); ?></td>
                        <td><?php echo esc_html(get_the_date('', $p)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button class="button button-primary" id="run-refresh" data-nonce="<?php echo esc_attr($nonce); ?>"><?php _e('Run Refresh', 'kotacom-ai'); ?></button>
                <span id="refresh-progress" style="margin-left:15px;"></span>
            </p>
        </div>
        <script>
        jQuery(function($){
            $('#select-all').on('change', function(){
                $('.post-select').prop('checked', $(this).is(':checked'));
            });

            // Category filter
            $('#cat-filter').on('change', function(){
                var val = $(this).val();
                $('#refresh-table tbody tr').each(function(){
                    var cats = $(this).data('cats').toString().split(',');
                    if(val==='all' || cats.includes(val)){
                        $(this).show();
                    }else{
                        $(this).hide();
                        $(this).find('.post-select').prop('checked', false);
                    }
                });
            });

            // Load template content into textarea
            $('#refresh-template').on('change', function(){
                var tid = $(this).val();
                if(!tid){ $('#refresh-prompt').val(''); return; }
                $.post(ajaxurl, { action: 'kotacom_get_template', nonce: '<?php echo esc_js($nonce); ?>', template_id: tid }, function(res){
                    if(res.success){
                        if(res.data.template){ $('#refresh-prompt').val(res.data.template.content); }
                    }
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
        });
        </script>
        <?php
    }

    /**
     * Simple Logs page
     */
    public function display_logs_page() {
        if (!current_user_can('manage_options')) return;
        $filter = isset($_GET['result']) ? sanitize_text_field($_GET['result']) : '';
        $logs = KotacomAI_Logger::get_logs(100, $filter);
        ?>
        <div class="wrap">
            <h1><?php _e('Kotacom AI Logs', 'kotacom-ai'); ?></h1>
            <p>
                <a href="<?php echo admin_url('admin.php?page=kotacom-ai-logs'); ?>" class="button <?php echo $filter==''?'button-primary':''; ?>">All</a>
                <a href="<?php echo admin_url('admin.php?page=kotacom-ai-logs&result=success'); ?>" class="button <?php echo $filter=='success'?'button-primary':''; ?>">Success</a>
                <a href="<?php echo admin_url('admin.php?page=kotacom-ai-logs&result=fail'); ?>" class="button <?php echo $filter=='fail'?'button-primary':''; ?>">Failed</a>
            </p>
            <table class="widefat fixed striped">
                <thead><tr><th>Date</th><th>Action</th><th>Post</th><th>Status</th><th>Message</th></tr></thead>
                <tbody>
                <?php foreach($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html($log->ts); ?></td>
                        <td><?php echo esc_html($log->action); ?></td>
                        <td>
                            <?php if($log->post_id): ?>
                                <a href="<?php echo get_edit_post_link($log->post_id); ?>" target="_blank">#<?php echo $log->post_id; ?></a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $log->success ? '✅' : '❌'; ?></td>
                        <td><?php echo esc_html($log->message); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
