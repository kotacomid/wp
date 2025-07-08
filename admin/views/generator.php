<?php
/**
 * Content Generator admin page - Enhanced with Inline Provider Selection
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get API handler for provider info
$api_handler = new KotacomAI_API_Handler();
$providers = $api_handler->get_providers();
$current_global_provider = get_option('kotacom_ai_api_provider', 'google_ai');
?>

<div class="wrap">
    <h1><?php _e('AI Content Generator', 'kotacom-ai'); ?></h1>
    
    <!-- Quick Start Guide -->
    <div class="quick-actions">
        <h4><?php _e('⚡ Quick Actions', 'kotacom-ai'); ?></h4>
        <a href="<?php echo admin_url('admin.php?page=kotacom-ai-settings'); ?>" class="button"><?php _e('🔧 Setup API Keys', 'kotacom-ai'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=kotacom-ai-keywords'); ?>" class="button"><?php _e('📝 Manage Keywords', 'kotacom-ai'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=kotacom-ai-prompts'); ?>" class="button"><?php _e('💬 Create Prompts', 'kotacom-ai'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=kotacom-ai-logs'); ?>" class="button"><?php _e('📊 View Results', 'kotacom-ai'); ?></a>
        
        <!-- Queue Debug Section -->
        <div style="margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
            <h4 style="margin: 0 0 10px 0;"><?php _e('🔧 Queue Debug', 'kotacom-ai'); ?></h4>
            <button type="button" id="check-queue-status" class="button"><?php _e('Check Queue Status', 'kotacom-ai'); ?></button>
            <button type="button" id="process-queue-manually" class="button button-secondary"><?php _e('Process Queue Now', 'kotacom-ai'); ?></button>
            <div id="queue-status-display" style="margin-top: 10px; font-family: monospace; font-size: 12px; background: white; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto; display: none;"></div>
        </div>
    </div>
    
    <div class="kotacom-ai-generator">
        <form id="kotacom-ai-generator-form">
            <?php wp_nonce_field('kotacom_ai_nonce', 'kotacom_ai_nonce'); ?>
            
            <!-- AI Provider Selection Section -->
            <div class="postbox">
                <h2 class="hndle">
                    <?php _e('AI Provider Selection', 'kotacom-ai'); ?>
                    <span class="provider-help-icon" title="<?php _e('Choose AI provider for this generation session. This will override your global setting temporarily.', 'kotacom-ai'); ?>">ℹ️</span>
                </h2>
                <div class="inside">
                    <div class="provider-selection-container">
                        <div class="provider-selection-main">
                            <label for="session-provider"><?php _e('AI Provider for this session:', 'kotacom-ai'); ?></label>
                            <select id="session-provider" name="session_provider" class="provider-selector">
                                <option value=""><?php _e('Use Global Setting', 'kotacom-ai'); ?> (<?php echo esc_html($providers[$current_global_provider]['name'] ?? 'Not Set'); ?>)</option>
                                <?php foreach ($providers as $key => $provider): ?>
                                    <option value="<?php echo esc_attr($key); ?>" 
                                            data-free="<?php echo $api_handler->is_free_tier($key) ? 'true' : 'false'; ?>"
                                            data-models="<?php echo esc_attr(json_encode($api_handler->get_provider_models($key))); ?>">
                                        <?php echo esc_html($provider['name']); ?>
                                        <?php if ($api_handler->is_free_tier($key)): ?>
                                            <span class="free-badge">FREE</span>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="provider-status" id="provider-status">
                                <span class="status-indicator" id="status-indicator">
                                    <span class="status-dot status-unknown"></span>
                                    <span class="status-text"><?php _e('Select provider to check status', 'kotacom-ai'); ?></span>
                                </span>
                            </div>
                        </div>
                        
                        <div class="provider-info-panel" id="provider-info-panel" style="display: none;">
                            <div class="provider-details">
                                <h4 id="provider-name"></h4>
                                <div class="provider-features">
                                    <div class="feature-item">
                                        <span class="feature-label"><?php _e('Pricing:', 'kotacom-ai'); ?></span>
                                        <span class="feature-value" id="provider-pricing"></span>
                                    </div>
                                    <div class="feature-item">
                                        <span class="feature-label"><?php _e('Speed:', 'kotacom-ai'); ?></span>
                                        <span class="feature-value" id="provider-speed"></span>
                                    </div>
                                    <div class="feature-item">
                                        <span class="feature-label"><?php _e('Quality:', 'kotacom-ai'); ?></span>
                                        <span class="feature-value" id="provider-quality"></span>
                                    </div>
                                </div>
                                <div class="provider-actions">
                                    <button type="button" id="test-provider-connection" class="button button-small">
                                        <?php _e('Test Connection', 'kotacom-ai'); ?>
                                    </button>
                                    <button type="button" id="configure-provider" class="button button-small">
                                        <?php _e('Configure', 'kotacom-ai'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Model Selection (Dynamic based on provider) -->
                    <div class="model-selection" id="model-selection" style="display: none;">
                        <label for="session-model"><?php _e('Model:', 'kotacom-ai'); ?></label>
                        <select id="session-model" name="session_model">
                            <!-- Options will be populated dynamically -->
                        </select>
                        <div class="model-info" id="model-info">
                            <!-- Model information will be displayed here -->
                        </div>
                    </div>
                    
                    <!-- Cost Estimation -->
                    <div class="cost-estimation" id="cost-estimation" style="display: none;">
                        <div class="cost-info">
                            <span class="cost-label"><?php _e('Estimated Cost:', 'kotacom-ai'); ?></span>
                            <span class="cost-value" id="estimated-cost">$0.00</span>
                            <span class="cost-note"><?php _e('(per generation)', 'kotacom-ai'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Keywords Selection -->
            <div class="postbox">
                <h2 class="hndle">
                    <?php _e('Select Keywords', 'kotacom-ai'); ?>
                    <span class="tooltip">ℹ️
                        <span class="tooltiptext"><?php _e('Choose keywords from your database or enter them manually. Keywords are the main topics your content will focus on.', 'kotacom-ai'); ?></span>
                    </span>
                </h2>
                <div class="inside">
                    <div class="info-card" style="margin-bottom: 15px;">
                        <p><strong><?php _e('💡 Tips for better keywords:', 'kotacom-ai'); ?></strong></p>
                        <ul style="margin-left: 20px;">
                            <li><?php _e('Use specific, long-tail keywords (e.g., "WordPress SEO optimization" vs "SEO")', 'kotacom-ai'); ?></li>
                            <li><?php _e('Select multiple related keywords for comprehensive content', 'kotacom-ai'); ?></li>
                            <li><?php _e('Organize keywords by tags for better management', 'kotacom-ai'); ?></li>
                        </ul>
                    </div>
                    <div class="keyword-selection-tabs">
                        <button type="button" class="tab-button active" data-tab="existing"><?php _e('From Database', 'kotacom-ai'); ?></button>
                        <button type="button" class="tab-button" data-tab="manual"><?php _e('Manual Input', 'kotacom-ai'); ?></button>
                    </div>
                    
                    <div id="existing-keywords" class="tab-content active">
                        <label for="tag-filter"><?php _e('Filter by Tag:', 'kotacom-ai'); ?></label>
                        <select id="tag-filter" name="tag_filter">
                            <option value=""><?php _e('All Tags', 'kotacom-ai'); ?></option>
                            <?php foreach ($tags as $tag): ?>
                                <option value="<?php echo esc_attr($tag); ?>"><?php echo esc_html($tag); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div id="keywords-list" class="keywords-checkboxes">
                            <!-- Keywords will be loaded via AJAX -->
                        </div>
                    </div>
                    
                    <div id="manual-keywords" class="tab-content">
                        <label for="manual-keywords-input"><?php _e('Enter Keywords (one per line):', 'kotacom-ai'); ?></label>
                        <textarea id="manual-keywords-input" name="manual_keywords" rows="5" placeholder="<?php _e('Enter keywords, one per line', 'kotacom-ai'); ?>"></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Prompt Selection -->
            <div class="postbox">
                <h2 class="hndle"><?php _e('Select Prompt Template', 'kotacom-ai'); ?></h2>
                <div class="inside">
                    <div class="prompt-selection-tabs">
                        <button type="button" class="tab-button active" data-tab="template"><?php _e('Use Template', 'kotacom-ai'); ?></button>
                        <button type="button" class="tab-button" data-tab="custom"><?php _e('Custom Prompt', 'kotacom-ai'); ?></button>
                    </div>
                    
                    <div id="template-prompt" class="tab-content active">
                        <label for="prompt-template-select"><?php _e('Select Template:', 'kotacom-ai'); ?></label>
                        <select id="prompt-template-select" name="prompt_template_id">
                            <option value=""><?php _e('Select a template', 'kotacom-ai'); ?></option>
                            <?php foreach ($prompts as $prompt): ?>
                                <option value="<?php echo esc_attr($prompt['id']); ?>" data-template="<?php echo esc_attr($prompt['prompt_template']); ?>">
                                    <?php echo esc_html($prompt['prompt_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div id="template-preview" class="template-preview">
                            <label><?php _e('Template Preview:', 'kotacom-ai'); ?></label>
                            <div class="template-content"></div>
                        </div>
                    </div>
                    
                    <div id="custom-prompt" class="tab-content">
                        <label for="custom-prompt-input"><?php _e('Custom Prompt:', 'kotacom-ai'); ?></label>
                        <textarea id="custom-prompt-input" name="custom_prompt" rows="5" placeholder="<?php _e('Enter your custom prompt. Use {keyword} as placeholder.', 'kotacom-ai'); ?>"></textarea>
                        <p class="description"><?php _e('Use {keyword} as a placeholder that will be replaced with the actual keyword.', 'kotacom-ai'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Content Parameters -->
            <div class="postbox">
                <h2 class="hndle">
                    <?php _e('Content Parameters', 'kotacom-ai'); ?>
                    <span class="tooltip">ℹ️
                        <span class="tooltiptext"><?php _e('Configure how your content should be written: tone, length, target audience, and industry focus.', 'kotacom-ai'); ?></span>
                    </span>
                </h2>
                <div class="inside">
                    <div class="info-card" style="margin-bottom: 15px;">
                        <p><strong><?php _e('🎯 Parameter Guide:', 'kotacom-ai'); ?></strong></p>
                        <ul style="margin-left: 20px;">
                            <li><strong><?php _e('Tone:', 'kotacom-ai'); ?></strong> <?php _e('How the content should sound (professional, casual, etc.)', 'kotacom-ai'); ?></li>
                            <li><strong><?php _e('Length:', 'kotacom-ai'); ?></strong> <?php _e('Longer content typically ranks better in search engines', 'kotacom-ai'); ?></li>
                            <li><strong><?php _e('Audience:', 'kotacom-ai'); ?></strong> <?php _e('Who will read this content (beginners, experts, general)', 'kotacom-ai'); ?></li>
                        </ul>
                    </div>
                    <div class="parameters-grid">
                        <div class="parameter-field">
                            <label for="tone">
                                <?php _e('Tone:', 'kotacom-ai'); ?>
                                <span class="tooltip">💡
                                    <span class="tooltiptext"><?php _e('Choose the writing style. Informative = educational, Formal = professional, Casual = friendly, Persuasive = sales-focused, Creative = artistic', 'kotacom-ai'); ?></span>
                                </span>
                            </label>
                            <select id="tone" name="tone">
                                <option value="informative"><?php _e('📚 Informative (Educational)', 'kotacom-ai'); ?></option>
                                <option value="formal"><?php _e('🎩 Formal (Professional)', 'kotacom-ai'); ?></option>
                                <option value="casual"><?php _e('😊 Casual (Friendly)', 'kotacom-ai'); ?></option>
                                <option value="persuasive"><?php _e('🎯 Persuasive (Sales)', 'kotacom-ai'); ?></option>
                                <option value="creative"><?php _e('🎨 Creative (Artistic)', 'kotacom-ai'); ?></option>
                            </select>
                        </div>
                        
                        <div class="parameter-field">
                            <label for="length">
                                <?php _e('Target Length:', 'kotacom-ai'); ?>
                                <span class="tooltip">📏
                                    <span class="tooltiptext"><?php _e('Word count target. Longer content (800+ words) typically ranks better in search engines. Choose based on your content strategy.', 'kotacom-ai'); ?></span>
                                </span>
                            </label>
                            <select id="length" name="length">
                                <option value="300"><?php _e('📄 Short (300 words) - Quick reads', 'kotacom-ai'); ?></option>
                                <option value="500" selected><?php _e('📰 Medium (500 words) - Standard', 'kotacom-ai'); ?></option>
                                <option value="800"><?php _e('📖 Long (800 words) - SEO-friendly', 'kotacom-ai'); ?></option>
                                <option value="1200"><?php _e('📚 Very Long (1200 words) - In-depth', 'kotacom-ai'); ?></option>
                                <option value="custom"><?php _e('⚙️ Custom Length', 'kotacom-ai'); ?></option>
                            </select>
                            
                            <div id="custom-length-container" style="display: none; margin-top: 10px;">
                                <label for="custom-length"><?php _e('Custom Length (words):', 'kotacom-ai'); ?></label>
                                <input type="number" id="custom-length" name="custom_length" min="100" max="5000" placeholder="e.g., 1500">
                                <div class="length-options">
                                    <label>
                                        <input type="checkbox" id="unlimited-length" name="unlimited_length">
                                        <?php _e('Unlimited length (remove restrictions)', 'kotacom-ai'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="parameter-field">
                            <label for="audience">
                                <?php _e('Target Audience:', 'kotacom-ai'); ?>
                                <span class="tooltip">👥
                                    <span class="tooltiptext"><?php _e('Who will read this content? Be specific: "small business owners", "fitness beginners", "experienced developers", etc.', 'kotacom-ai'); ?></span>
                                </span>
                            </label>
                            <input type="text" id="audience" name="audience" value="general" placeholder="<?php _e('e.g., beginners, professionals, small business owners', 'kotacom-ai'); ?>">
                            <div class="field-help"><?php _e('💡 Be specific: "WordPress beginners" instead of just "beginners"', 'kotacom-ai'); ?></div>
                        </div>
                        
                        <div class="parameter-field">
                            <label for="niche">
                                <?php _e('Industry/Niche:', 'kotacom-ai'); ?>
                                <span class="tooltip">🏢
                                    <span class="tooltiptext"><?php _e('The industry or topic area. This helps AI understand the context and use appropriate terminology.', 'kotacom-ai'); ?></span>
                                </span>
                            </label>
                            <input type="text" id="niche" name="niche" placeholder="<?php _e('e.g., technology, health, finance, e-commerce', 'kotacom-ai'); ?>">
                            <div class="field-help"><?php _e('💡 Examples: "SaaS marketing", "personal finance", "web development"', 'kotacom-ai'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- WordPress Post Settings -->
            <div class="postbox">
                <h2 class="hndle">
                    <?php _e('WordPress Post Settings', 'kotacom-ai'); ?>
                    <span class="tooltip">⚙️
                        <span class="tooltiptext"><?php _e('Configure how the generated content will be saved in WordPress. Choose post type, status, categories, and tags.', 'kotacom-ai'); ?></span>
                    </span>
                </h2>
                <div class="inside">
                    <div class="info-card" style="margin-bottom: 15px;">
                        <p><strong><?php _e('📝 WordPress Integration:', 'kotacom-ai'); ?></strong></p>
                        <ul style="margin-left: 20px;">
                            <li><strong><?php _e('Draft Status:', 'kotacom-ai'); ?></strong> <?php _e('Recommended for review before publishing', 'kotacom-ai'); ?></li>
                            <li><strong><?php _e('Categories:', 'kotacom-ai'); ?></strong> <?php _e('Help organize content and improve SEO', 'kotacom-ai'); ?></li>
                            <li><strong><?php _e('Tags:', 'kotacom-ai'); ?></strong> <?php _e('Use relevant keywords as tags for better discoverability', 'kotacom-ai'); ?></li>
                        </ul>
                    </div>
                    <div class="post-settings-grid">
                        <div class="setting-field">
                            <label for="post-status"><?php _e('Post Status:', 'kotacom-ai'); ?></label>
                            <select id="post-status" name="post_status">
                                <option value="draft" selected><?php _e('Draft', 'kotacom-ai'); ?></option>
                                <option value="publish"><?php _e('Publish', 'kotacom-ai'); ?></option>
                            </select>
                        </div>
                        
                        <div class="setting-field">
                            <label for="post-type"><?php _e('Post Type:', 'kotacom-ai'); ?></label>
                            <select id="post-type" name="post_type">
                                <?php foreach ($post_types as $post_type): ?>
                                    <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($post_type->name, 'post'); ?>>
                                        <?php echo esc_html($post_type->label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="setting-field">
                            <label for="post-categories"><?php _e('Categories:', 'kotacom-ai'); ?></label>
                            <select id="post-categories" name="categories[]" multiple>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category->term_id); ?>">
                                        <?php echo esc_html($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="setting-field">
                            <label for="post-tags"><?php _e('Tags:', 'kotacom-ai'); ?></label>
                            <input type="text" id="post-tags" name="tags" placeholder="<?php _e('Comma-separated tags', 'kotacom-ai'); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Generate Button -->
            <div class="submit-section">
                <button type="submit" class="button button-primary button-large" id="generate-content-btn">
                    <?php _e('Generate Content', 'kotacom-ai'); ?>
                </button>
                <span class="spinner"></span>
                
                <!-- Generation Summary -->
                <div class="generation-summary" id="generation-summary" style="display: none;">
                    <div class="summary-item">
                        <span class="summary-label"><?php _e('Provider:', 'kotacom-ai'); ?></span>
                        <span class="summary-value" id="summary-provider"></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label"><?php _e('Keywords:', 'kotacom-ai'); ?></span>
                        <span class="summary-value" id="summary-keywords"></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label"><?php _e('Estimated Cost:', 'kotacom-ai'); ?></span>
                        <span class="summary-value" id="summary-cost"></span>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Results Section -->
        <div id="generation-results" class="generation-results" style="display: none;">
            <h2><?php _e('Generation Results', 'kotacom-ai'); ?></h2>
            <div class="results-content"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Provider information database
    const providerInfo = {
        'google_ai': {
            name: 'Google AI (Gemini)',
            pricing: 'Free Tier Available',
            speed: '⚡⚡⚡⚡ Very Fast',
            quality: '⭐⭐⭐⭐⭐ Excellent',
            description: 'Google\'s latest AI model with excellent performance and generous free tier.'
        },
        'groq': {
            name: 'Groq (Fast Inference)',
            pricing: 'Free Tier Available',
            speed: '⚡⚡⚡⚡⚡ Ultra Fast',
            quality: '⭐⭐⭐⭐ Very Good',
            description: 'Ultra-fast inference with competitive quality and free tier.'
        },
        'openai': {
            name: 'OpenAI',
            pricing: 'Paid Service',
            speed: '⚡⚡⚡ Fast',
            quality: '⭐⭐⭐⭐⭐ Excellent',
            description: 'Industry-leading AI models with premium quality.'
        },
        'anthropic': {
            name: 'Anthropic Claude',
            pricing: 'Free Credits Available',
            speed: '⚡⚡⚡ Fast',
            quality: '⭐⭐⭐⭐⭐ Excellent',
            description: 'Advanced AI with strong reasoning capabilities.'
        },
        'cohere': {
            name: 'Cohere',
            pricing: 'Free Tier Available',
            speed: '⚡⚡⚡ Fast',
            quality: '⭐⭐⭐⭐ Very Good',
            description: 'Enterprise-focused AI with good performance.'
        },
        'huggingface': {
            name: 'Hugging Face',
            pricing: 'Free Tier Available',
            speed: '⚡⚡ Moderate',
            quality: '⭐⭐⭐ Good',
            description: 'Open-source models with free tier access.'
        },
        'together': {
            name: 'Together AI',
            pricing: 'Free Credits Available',
            speed: '⚡⚡⚡ Fast',
            quality: '⭐⭐⭐⭐ Very Good',
            description: 'Optimized open-source models with competitive pricing.'
        },
        'replicate': {
            name: 'Replicate',
            pricing: 'Free Credits Available',
            speed: '⚡⚡ Moderate',
            quality: '⭐⭐⭐ Good',
            description: 'Easy access to various open-source models.'
        },
        'openrouter': {
            name: 'OpenRouter',
            pricing: 'Paid Service (Aggregator)',
            speed: '⚡⚡⚡⚡ Very Fast',
            quality: '⭐⭐⭐⭐⭐ Excellent (Varies by model)',
            description: 'Access to many models from various providers through a single API. Pricing varies by model.'
        },
        'perplexity': {
            name: 'Perplexity AI',
            pricing: 'Paid Service',
            speed: '⚡⚡⚡⚡ Very Fast',
            quality: '⭐⭐⭐⭐ Excellent',
            description: 'Conversational AI focused on accuracy and real-time information. Offers online models.'
        }
    };
    
    // Tab switching
    $('.tab-button').on('click', function() {
        var tab = $(this).data('tab');
        var container = $(this).closest('.postbox');
        
        container.find('.tab-button').removeClass('active');
        container.find('.tab-content').removeClass('active');
        
        $(this).addClass('active');
        container.find('#' + tab + '-keywords, #' + tab + '-prompt').addClass('active');
    });
    
    // Provider selection handling
    $('#session-provider').on('change', function() {
        const selectedProvider = $(this).val();
        
        if (selectedProvider) {
            showProviderInfo(selectedProvider);
            loadProviderModels(selectedProvider);
            checkProviderStatus(selectedProvider);
            showModelSelection();
            updateCostEstimation();
        } else {
            hideProviderInfo();
            hideModelSelection();
            hideCostEstimation();
            resetProviderStatus();
        }
    });
    
    // Custom length handling
    $('#length').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#custom-length-container').show();
        } else {
            $('#custom-length-container').hide();
        }
        updateCostEstimation();
    });
    
    // Unlimited length checkbox
    $('#unlimited-length').on('change', function() {
        if ($(this).is(':checked')) {
            $('#custom-length').prop('disabled', true).val('');
        } else {
            $('#custom-length').prop('disabled', false);
        }
    });
    
    // Load keywords when tag filter changes
    $('#tag-filter').on('change', function() {
        loadKeywords();
    });
    
    // Queue Debug Functions
    $('#check-queue-status').on('click', function() {
        var $btn = $(this);
        var $display = $('#queue-status-display');
        
        $btn.prop('disabled', true).text('Checking...');
        
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_get_queue_debug',
                nonce: kotacomAI.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '<strong>Queue Status:</strong><br>';
                    html += 'Total: ' + data.queue_status.total + '<br>';
                    html += 'Pending: ' + data.queue_status.pending + '<br>';
                    html += 'Processing: ' + data.queue_status.processing + '<br>';
                    html += 'Completed: ' + data.queue_status.completed + '<br>';
                    html += 'Failed: ' + data.queue_status.failed + '<br>';
                    html += 'Retry: ' + data.queue_status.retry + '<br><br>';
                    
                    html += '<strong>Cron Status:</strong> ' + data.cron_status + '<br>';
                    html += '<strong>Queue Paused:</strong> ' + (data.is_paused ? 'Yes' : 'No') + '<br>';
                    html += '<strong>Last Process:</strong> ' + data.last_process_time + '<br>';
                    html += '<strong>Total Batches:</strong> ' + data.total_batches + '<br>';
                    html += '<strong>Queue Size:</strong> ' + data.queue_option_size + '<br><br>';
                    
                    if (data.recent_items && data.recent_items.length > 0) {
                        html += '<strong>Recent Items:</strong><br>';
                        data.recent_items.forEach(function(item) {
                            html += '- ' + item.action + ' (' + item.status + ') - ' + (item.data.keyword || 'N/A') + '<br>';
                        });
                    }
                    
                    if (data.failed_items && data.failed_items.length > 0) {
                        html += '<br><strong>Failed Items:</strong><br>';
                        data.failed_items.forEach(function(item) {
                            html += '- ' + item.action + ': ' + (item.last_error || 'Unknown error') + '<br>';
                        });
                    }
                    
                    $display.html(html).show();
                } else {
                    $display.html('<span style="color: red;">Error: ' + response.data.message + '</span>').show();
                }
            },
            error: function() {
                $display.html('<span style="color: red;">AJAX Error occurred</span>').show();
            },
            complete: function() {
                $btn.prop('disabled', false).text('Check Queue Status');
            }
        });
    });
    
    $('#process-queue-manually').on('click', function() {
        var $btn = $(this);
        var $display = $('#queue-status-display');
        
        $btn.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_process_queue_manually',
                nonce: kotacomAI.nonce
            },
            success: function(response) {
                if (response.success) {
                    var html = '<span style="color: green;">' + response.data.message + '</span><br><br>';
                    var status = response.data.queue_status;
                    html += '<strong>After Processing:</strong><br>';
                    html += 'Total: ' + status.total + '<br>';
                    html += 'Pending: ' + status.pending + '<br>';
                    html += 'Processing: ' + status.processing + '<br>';
                    html += 'Completed: ' + status.completed + '<br>';
                    html += 'Failed: ' + status.failed + '<br>';
                    html += 'Retry: ' + status.retry + '<br>';
                    
                    $display.html(html).show();
                } else {
                    $display.html('<span style="color: red;">Error: ' + response.data.message + '</span>').show();
                }
            },
            error: function() {
                $display.html('<span style="color: red;">AJAX Error occurred</span>').show();
            },
            complete: function() {
                $btn.prop('disabled', false).text('Process Queue Now');
            }
        });
    });
    
    // Template preview
    $('#prompt-template-select').on('change', function() {
        var template = $(this).find(':selected').data('template');
        if (template) {
            $('#template-preview .template-content').text(template);
            $('#template-preview').show();
        } else {
            $('#template-preview').hide();
        }
    });
    
    // Test provider connection
    $('#test-provider-connection').on('click', function() {
        const provider = $('#session-provider').val();
        if (!provider) return;
        
        const $btn = $(this);
        const $status = $('#status-indicator');
        
        $btn.prop('disabled', true).text('Testing...');
        $status.find('.status-dot').removeClass().addClass('status-dot status-testing');
        $status.find('.status-text').text('Testing connection...');
        
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_test_provider_connection',
                nonce: kotacomAI.nonce,
                provider: provider
            },
            success: function(response) {
                if (response.success) {
                    $status.find('.status-dot').removeClass().addClass('status-dot status-connected');
                    $status.find('.status-text').text('Connected successfully');
                } else {
                    $status.find('.status-dot').removeClass().addClass('status-dot status-error');
                    $status.find('.status-text').text('Connection failed: ' + response.data.message);
                }
            },
            error: function() {
                $status.find('.status-dot').removeClass().addClass('status-dot status-error');
                $status.find('.status-text').text('Connection test failed');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Test Connection');
            }
        });
    });
    
    // Configure provider button
    $('#configure-provider').on('click', function() {
        window.open(kotacomAI.settingsUrl, '_blank');
    });
    
    // Form submission
    $('#kotacom-ai-generator-form').on('submit', function(e) {
        e.preventDefault();
        generateContent();
    });
    
    // Load initial keywords
    loadKeywords();
    
    // Initialize provider status
    resetProviderStatus();
    
    function showProviderInfo(provider) {
        const info = providerInfo[provider];
        if (!info) return;
        
        $('#provider-name').text(info.name);
        $('#provider-pricing').text(info.pricing);
        $('#provider-speed').text(info.speed);
        $('#provider-quality').text(info.quality);
        
        $('#provider-info-panel').show();
    }
    
    function hideProviderInfo() {
        $('#provider-info-panel').hide();
    }
    
    function loadProviderModels(provider) {
        const $option = $('#session-provider option[value="' + provider + '"]');
        let models = $option.data('models');
        
        // Handle both string and object data
        if (typeof models === 'string') {
            try {
                models = JSON.parse(models);
            } catch (e) {
                console.error('Failed to parse provider models:', e);
                models = {};
            }
        } else if (typeof models !== 'object' || models === null) {
            models = {};
        }
        
        const $modelSelect = $('#session-model');
        $modelSelect.empty();
        
        if (Object.keys(models).length === 0) {
            $modelSelect.append('<option value="">' + 'No models available' + '</option>');
            return;
        }
        
        $.each(models, function(key, name) {
            $modelSelect.append('<option value="' + key + '">' + name + '</option>');
        });
        
        // Show model info for first model
        if (Object.keys(models).length > 0) {
            const firstModel = Object.keys(models)[0];
            showModelInfo(provider, firstModel);
        }
    }
    
    function showModelInfo(provider, model) {
        // This would show model-specific information
        $('#model-info').html('<small>Model: ' + model + '</small>');
    }
    
    function showModelSelection() {
        $('#model-selection').show();
    }
    
    function hideModelSelection() {
        $('#model-selection').hide();
    }
    
    function checkProviderStatus(provider) {
        const $status = $('#status-indicator');
        
        $status.find('.status-dot').removeClass().addClass('status-dot status-checking');
        $status.find('.status-text').text('Checking provider status...');
        
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_check_provider_status',
                nonce: kotacomAI.nonce,
                provider: provider
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.configured) {
                        $status.find('.status-dot').removeClass().addClass('status-dot status-connected');
                        $status.find('.status-text').text('Ready to use');
                    } else {
                        $status.find('.status-dot').removeClass().addClass('status-dot status-warning');
                        $status.find('.status-text').text('Not configured - will use global setting');
                    }
                } else {
                    $status.find('.status-dot').removeClass().addClass('status-dot status-error');
                    $status.find('.status-text').text('Status check failed');
                }
            },
            error: function() {
                $status.find('.status-dot').removeClass().addClass('status-dot status-error');
                $status.find('.status-text').text('Unable to check status');
            }
        });
    }
    
    function resetProviderStatus() {
        const $status = $('#status-indicator');
        $status.find('.status-dot').removeClass().addClass('status-dot status-unknown');
        $status.find('.status-text').text('Select provider to check status');
    }
    
    function updateCostEstimation() {
        const provider = $('#session-provider').val();
        const length = $('#length').val();
        const customLength = $('#custom-length').val();
        const unlimited = $('#unlimited-length').is(':checked');
        
        if (!provider) {
            hideCostEstimation();
            return;
        }
        
        // Simple cost estimation logic
        let estimatedCost = 0;
        let targetLength = 500;
        
        if (length === 'custom') {
            if (unlimited) {
                targetLength = 2000; // Assume average for unlimited
            } else if (customLength) {
                targetLength = parseInt(customLength);
            }
        } else {
            targetLength = parseInt(length);
        }
        
        // Cost calculation based on provider and length
        const costPerWord = getCostPerWord(provider);
        estimatedCost = (targetLength * costPerWord).toFixed(4);
        
        $('#estimated-cost').text('$' + estimatedCost);
        $('#cost-estimation').show();
    }
    
    function getCostPerWord(provider) {
        const costs = {
            'google_ai': 0.0000,  // Free tier
            'groq': 0.0000,       // Free tier
            'openai': 0.00002,    // Approximate
            'anthropic': 0.00001, // Approximate
            'cohere': 0.0000,     // Free tier
            'huggingface': 0.0000, // Free tier
            'together': 0.00001,  // Approximate
            'replicate': 0.00001, // Approximate
            'openrouter': 0.000015, // Approximate, varies by model
            'perplexity': 0.000018 // Approximate, varies by model
        };
        
        return costs[provider] || 0;
    }
    
    function hideCostEstimation() {
        $('#cost-estimation').hide();
    }
    
    function loadKeywords() {
        var tagFilter = $('#tag-filter').val();
        
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_get_keywords',
                nonce: kotacomAI.nonce,
                tag_filter: tagFilter,
                per_page: 100
            },
            success: function(response) {
                if (response.success) {
                    var html = '';
                    $.each(response.data.keywords, function(index, keyword) {
                        html += '<label class="keyword-checkbox">';
                        html += '<input type="checkbox" name="selected_keywords[]" value="' + keyword.keyword + '">';
                        html += '<span>' + keyword.keyword + '</span>';
                        if (keyword.tags) {
                            html += '<small class="keyword-tags">(' + keyword.tags + ')</small>';
                        }
                        html += '</label>';
                    });
                    $('#keywords-list').html(html);
                }
            }
        });
    }
    
    function generateContent() {
        var $btn = $('#generate-content-btn');
        var $spinner = $('.spinner');
        
        // Collect form data
        var keywords = [];
        
        if ($('#existing-keywords').hasClass('active')) {
            $('input[name="selected_keywords[]"]:checked').each(function() {
                keywords.push($(this).val());
            });
        } else {
            var manualKeywords = $('#manual-keywords-input').val().split('\n');
            $.each(manualKeywords, function(index, keyword) {
                keyword = keyword.trim();
                if (keyword) {
                    keywords.push(keyword);
                }
            });
        }
        
        if (keywords.length === 0) {
            alert('<?php _e('Please select or enter at least one keyword.', 'kotacom-ai'); ?>');
            return;
        }
        
        var promptTemplate = '';
        if ($('#template-prompt').hasClass('active')) {
            promptTemplate = $('#prompt-template-select').find(':selected').data('template');
            if (!promptTemplate) {
                alert('<?php _e('Please select a prompt template.', 'kotacom-ai'); ?>');
                return;
            }
        } else {
            promptTemplate = $('#custom-prompt-input').val();
            if (!promptTemplate) {
                alert('<?php _e('Please enter a custom prompt.', 'kotacom-ai'); ?>');
                return;
            }
        }
        
        // Get length value
        var length = $('#length').val();
        var finalLength = length;
        
        if (length === 'custom') {
            if ($('#unlimited-length').is(':checked')) {
                finalLength = 'unlimited';
            } else {
                finalLength = $('#custom-length').val() || '500';
            }
        }
        
        // Show generation summary
        updateGenerationSummary(keywords, finalLength);
        
        // Show loading state
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_generate_content',
                nonce: kotacomAI.nonce,
                keywords: keywords,
                prompt_template: promptTemplate,
                session_provider: $('#session-provider').val(),
                session_model: $('#session-model').val(),
                tone: $('#tone').val(),
                length: finalLength,
                audience: $('#audience').val(),
                niche: $('#niche').val(),
                post_type: $('#post-type').val(),
                post_status: $('#post-status').val(),
                categories: $('#post-categories').val() || [],
                tags: $('#post-tags').val()
            },
            success: function(response) {
                displayResults(response);
            },
            error: function() {
                alert(kotacomAI.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    }
    
    function updateGenerationSummary(keywords, length) {
        const provider = $('#session-provider').val() || 'Global Setting';
        const providerName = provider === 'Global Setting' ? provider : (providerInfo[provider]?.name || provider);
        
        $('#summary-provider').text(providerName);
        $('#summary-keywords').text(keywords.length + ' keyword(s)');
        $('#summary-cost').text($('#estimated-cost').text() || 'Free');
        $('#generation-summary').show();
    }
    
    function displayResults(response) {
        var html = '';
        
        if (response.success) {
            html += '<div class="notice notice-success"><p>' + response.data.message + '</p></div>';
            
            if (response.data.results) {
                html += '<div class="results-table">';
                html += '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr><th>Keyword</th><th>Status</th><th>Message</th><th>Action</th></tr></thead>';
                html += '<tbody>';
                
                $.each(response.data.results, function(index, result) {
                    html += '<tr>';
                    html += '<td>' + result.keyword + '</td>';
                    html += '<td><span class="status-' + result.status + '">' + result.status + '</span></td>';
                    html += '<td>' + result.message + '</td>';
                    html += '<td>';
                    if (result.post_id) {
                        html += '<a href="' + '<?php echo admin_url('post.php?action=edit&post='); ?>' + result.post_id + '" target="_blank">Edit Post</a>';
                    }
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                html += '</div>';
            }
        } else {
            html += '<div class="notice notice-error"><p>' + response.data.message + '</p></div>';
        }
        
        $('#generation-results .results-content').html(html);
        $('#generation-results').show();
    }
});
</script>
