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
        
        // Enhanced post query with pagination support
        $posts_per_page = 50;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $date_filter = isset($_GET['date_filter']) ? sanitize_text_field($_GET['date_filter']) : 'all';
        
        $args = array(
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'post_status' => 'publish',
            'post_type' => 'post',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
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
            
            <!-- Enhanced Filters -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <label for="refresh-template"><strong><?php _e('Template:', 'kotacom-ai'); ?></strong></label>
                    <select id="refresh-template">
                        <option value=""><?php _e('— Select template —', 'kotacom-ai'); ?></option>
                        <?php
                        $templates = get_posts(array('post_type' => 'kotacom_template', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                        foreach($templates as $t): ?>
                            <option value="<?php echo esc_attr($t->ID); ?>"><?php echo esc_html($t->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="cat-filter" style="margin-left: 20px;"><strong><?php _e('Category:', 'kotacom-ai'); ?></strong></label>
                    <select id="cat-filter">
                        <option value="all"><?php _e('All Categories', 'kotacom-ai'); ?></option>
                        <?php $all_cats = get_categories(array('hide_empty'=>false));
                        foreach($all_cats as $c): ?>
                            <option value="<?php echo esc_attr($c->term_id); ?>"><?php echo esc_html($c->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="date-filter" style="margin-left: 20px;"><strong><?php _e('Date:', 'kotacom-ai'); ?></strong></label>
                    <select id="date-filter" onchange="window.location.href=this.value;">
                        <option value="<?php echo admin_url('admin.php?page=kotacom-ai-refresh&date_filter=all'); ?>" <?php selected($date_filter, 'all'); ?>><?php _e('All Dates', 'kotacom-ai'); ?></option>
                        <option value="<?php echo admin_url('admin.php?page=kotacom-ai-refresh&date_filter=last_week'); ?>" <?php selected($date_filter, 'last_week'); ?>><?php _e('Last Week', 'kotacom-ai'); ?></option>
                        <option value="<?php echo admin_url('admin.php?page=kotacom-ai-refresh&date_filter=last_month'); ?>" <?php selected($date_filter, 'last_month'); ?>><?php _e('Last Month', 'kotacom-ai'); ?></option>
                        <option value="<?php echo admin_url('admin.php?page=kotacom-ai-refresh&date_filter=last_3_months'); ?>" <?php selected($date_filter, 'last_3_months'); ?>><?php _e('Last 3 Months', 'kotacom-ai'); ?></option>
                        <option value="<?php echo admin_url('admin.php?page=kotacom-ai-refresh&date_filter=last_year'); ?>" <?php selected($date_filter, 'last_year'); ?>><?php _e('Last Year', 'kotacom-ai'); ?></option>
                        <option value="<?php echo admin_url('admin.php?page=kotacom-ai-refresh&date_filter=older_than_year'); ?>" <?php selected($date_filter, 'older_than_year'); ?>><?php _e('Older than 1 Year', 'kotacom-ai'); ?></option>
                    </select>
                </div>
                
                <div class="alignright actions">
                    <input type="checkbox" id="update-date" /> 
                    <label for="update-date"><?php _e('Update post date to now', 'kotacom-ai'); ?></label>
                </div>
            </div>
            
            <p><strong><?php _e('Total Posts:', 'kotacom-ai'); ?></strong> <?php echo number_format($total_posts); ?> | 
               <strong><?php _e('Showing:', 'kotacom-ai'); ?></strong> <?php echo count($posts); ?> posts</p>
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
     * Enhanced Logs page with better filtering and display
     */
    public function display_logs_page() {
        if (!current_user_can('manage_options')) return;
        
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
