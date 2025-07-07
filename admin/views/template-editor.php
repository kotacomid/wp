<?php
/**
 * Template Editor Interface
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get template manager to fetch existing templates
$template_manager = new KotacomAI_Template_Manager();
$existing_templates = $template_manager->get_templates();
?>

<div id="template-editor"><!-- ADDED WRAPPER FOR JS INIT -->

<div class="wrap">
    <h1><?php _e('AI Content Template Editor', 'kotacom-ai'); ?></h1>
    <div class="template-generate-bar" style="margin-bottom:20px;">
        <input type="text" id="generate-keyword" placeholder="<?php _e('Enter keyword...', 'kotacom-ai'); ?>" style="width:250px;">
        <button id="generate-post" class="button button-primary"><?php _e('Generate Post', 'kotacom-ai'); ?></button>
    </div>
    <div class="template-editor-container">
        <!-- Template Header -->
        <div class="template-header">
            <div class="template-info">
                <input type="text" id="template-title" placeholder="<?php _e('Template Name', 'kotacom-ai'); ?>" class="large-text">
                <select id="template-type">
                    <option value="visual"><?php _e('Visual Builder', 'kotacom-ai'); ?></option>
                    <option value="shortcode"><?php _e('Shortcode Editor', 'kotacom-ai'); ?></option>
                    <option value="gutenberg"><?php _e('Gutenberg Blocks', 'kotacom-ai'); ?></option>
                </select>
                <select id="load-template">
                    <option value=""><?php _e('Load Existing Template', 'kotacom-ai'); ?></option>
                    <?php foreach ($existing_templates as $template): ?>
                        <option value="<?php echo esc_attr($template->ID); ?>"><?php echo esc_html($template->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="template-actions">
                <button id="preview-template" class="button"><?php _e('Preview', 'kotacom-ai'); ?></button>
                <button id="save-template" class="button button-primary"><?php _e('Save Template', 'kotacom-ai'); ?></button>
                <button id="duplicate-template" class="button"><?php _e('Duplicate', 'kotacom-ai'); ?></button>
            </div>
        </div>
        
        <!-- Template Editor Modes -->
        <div id="template-editor-modes" class="template-editor-modes">
            
            <!-- Visual Builder -->
            <div id="visual-builder" class="editor-mode">
                <div class="builder-layout">
                    <!-- Component Palette -->
                    <div class="component-palette">
                        <h3><?php _e('Components', 'kotacom-ai'); ?></h3>
                        
                        <div class="component-group">
                            <h4><?php _e('AI Components', 'kotacom-ai'); ?></h4>
                            <div class="template-component" data-component-type="ai-content">
                                <span class="component-icon">🤖</span>
                                <span class="component-label"><?php _e('AI Content', 'kotacom-ai'); ?></span>
                            </div>
                            <div class="template-component" data-component-type="ai-section">
                                <span class="component-icon">📝</span>
                                <span class="component-label"><?php _e('AI Section', 'kotacom-ai'); ?></span>
                            </div>
                            <div class="template-component" data-component-type="ai-list">
                                <span class="component-icon">📋</span>
                                <span class="component-label"><?php _e('AI List', 'kotacom-ai'); ?></span>
                            </div>
                        </div>
                        
                        <div class="component-group">
                            <h4><?php _e('Layout Components', 'kotacom-ai'); ?></h4>
                            <div class="template-component" data-component-type="static-content">
                                <span class="component-icon">📄</span>
                                <span class="component-label"><?php _e('Static Content', 'kotacom-ai'); ?></span>
                            </div>
                            <div class="template-component" data-component-type="conditional">
                                <span class="component-icon">🔀</span>
                                <span class="component-label"><?php _e('Conditional', 'kotacom-ai'); ?></span>
                            </div>
                            <div class="template-component" data-component-type="separator">
                                <span class="component-icon">➖</span>
                                <span class="component-label"><?php _e('Separator', 'kotacom-ai'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Template Builder -->
                    <div class="template-builder-area">
                        <div class="builder-header">
                            <h3><?php _e('Template Structure', 'kotacom-ai'); ?></h3>
                            <div class="builder-tools">
                                <button id="clear-template" class="button"><?php _e('Clear All', 'kotacom-ai'); ?></button>
                                <!-- These buttons will open the shortcode builder modal for insertion -->
                                <button id="add-ai-content" class="button"><?php _e('+ AI Content', 'kotacom-ai'); ?></button>
                                <button id="add-ai-section" class="button"><?php _e('+ AI Section', 'kotacom-ai'); ?></button>
                                <button id="add-ai-list" class="button"><?php _e('+ AI List', 'kotacom-ai'); ?></button>
                                <button id="add-conditional" class="button"><?php _e('+ Conditional', 'kotacom-ai'); ?></button>
                            </div>
                        </div>
                        
                        <div id="template-builder" class="template-builder">
                            <div class="builder-placeholder">
                                <p><?php _e('Drag components here to build your template', 'kotacom-ai'); ?></p>
                                <p class="description"><?php _e('Or click the + buttons to add components', 'kotacom-ai'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Properties Panel -->
                    <div class="properties-panel">
                        <h3><?php _e('Properties', 'kotacom-ai'); ?></h3>
                        
                        <div class="property-group">
                            <h4><?php _e('Template Settings', 'kotacom-ai'); ?></h4>
                            <label>
                                <input type="checkbox" id="auto-generate"> 
                                <?php _e('Auto-generate on publish', 'kotacom-ai'); ?>
                            </label>
                            <label>
                                <?php _e('Cache Duration:', 'kotacom-ai'); ?>
                                <select id="cache-duration">
                                    <option value="3600"><?php _e('1 Hour', 'kotacom-ai'); ?></option>
                                    <option value="86400"><?php _e('1 Day', 'kotacom-ai'); ?></option>
                                    <option value="604800"><?php _e('1 Week', 'kotacom-ai'); ?></option>
                                    <option value="0"><?php _e('No Cache', 'kotacom-ai'); ?></option>
                                </select>
                            </label>
                        </div>
                        
                        <div class="property-group">
                            <h4><?php _e('Image Generation', 'kotacom-ai'); ?></h4>
                            <label>
                                <input type="checkbox" id="auto-generate-images"> 
                                <?php _e('Auto-generate images in content', 'kotacom-ai'); ?>
                            </label>
                            <label>
                                <?php _e('Default Image Provider:', 'kotacom-ai'); ?>
                                <select id="template-image-provider">
                                    <?php
                                    $image_generator = new KotacomAI_Image_Generator();
                                    $image_providers = $image_generator->get_providers();
                                    foreach ($image_providers as $key => $name):
                                    ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <?php _e('Default Image Size:', 'kotacom-ai'); ?>
                                <select id="template-image-size">
                                    <option value="400x300"><?php _e('Small (400x300)', 'kotacom-ai'); ?></option>
                                    <option value="800x600" selected><?php _e('Medium (800x600)', 'kotacom-ai'); ?></option>
                                    <option value="1200x800"><?php _e('Large (1200x800)', 'kotacom-ai'); ?></option>
                                    <option value="800x800"><?php _e('Square (800x800)', 'kotacom-ai'); ?></option>
                                </select>
                            </label>
                            <label>
                                <input type="checkbox" id="generate-featured-image"> 
                                <?php _e('Generate featured image for posts', 'kotacom-ai'); ?>
                            </label>
                        </div>
                        
                        <div class="property-group">
                            <h4><?php _e('Variables', 'kotacom-ai'); ?></h4>
                            <div id="variables-list">
                                <!-- Variables will be added here -->
                            </div>
                            <button id="add-variable" class="button button-small"><?php _e('Add Variable', 'kotacom-ai'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shortcode Editor -->
            <div id="shortcode-editor" class="editor-mode" style="display: none;">
                <div class="shortcode-editor-layout">
                    <div class="editor-toolbar">
                        <button id="add-ai-content" class="button"><?php _e('AI Content', 'kotacom-ai'); ?></button>
                        <button id="add-ai-section" class="button"><?php _e('AI Section', 'kotacom-ai'); ?></button>
                        <button id="add-ai-list" class="button"><?php _e('AI List', 'kotacom-ai'); ?></button>
                        <button id="add-conditional" class="button"><?php _e('Conditional', 'kotacom-ai'); ?></button>
                        <!-- Variable button here would open a modal to insert {variable_name} -->
                    </div>
                    
                    <div class="editor-content">
                        <textarea id="template-content" rows="20" class="large-text code" placeholder="<?php _e('Enter your template content with shortcodes...', 'kotacom-ai'); ?>"></textarea>
                    </div>
                    
                    <div class="editor-help">
                        <h4><?php _e('Available Shortcodes:', 'kotacom-ai'); ?></h4>
                        <div class="shortcode-examples">
                            <div class="shortcode-example">
                                <code>[ai_content type="paragraph" prompt="Write about {keyword}" length="200"]</code>
                                <p><?php _e('Generates AI content based on prompt', 'kotacom-ai'); ?></p>
                            </div>
                            <div class="shortcode-example">
                                <code>[ai_image prompt="{keyword}" size="800x600" featured="no" provider="unsplash"]</code>
                                <p><?php _e('Generates AI-powered images with multiple provider support', 'kotacom-ai'); ?></p>
                            </div>
                            <div class="shortcode-example">
                                <code>[ai_section title="About {keyword}"]...[/ai_section]</code>
                                <p><?php _e('Creates a structured section with AI content', 'kotacom-ai'); ?></p>
                            </div>
                            <div class="shortcode-example">
                                <code>[ai_list prompt="5 benefits of {keyword}" type="ul" length="5"]</code>
                                <p><?php _e('Generates an AI-powered list', 'kotacom-ai'); ?></p>
                            </div>
                            <div class="shortcode-example">
                                <code>[ai_conditional if="post_type" equals="product"]...[/ai_conditional]</code>
                                <p><?php _e('Shows content based on conditions', 'kotacom-ai'); ?></p>
                            </div>
                            <div class="shortcode-example">
                                <code>{your_variable_name}</code>
                                <p><?php _e('Inserts a custom variable value', 'kotacom-ai'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gutenberg Editor -->
            <div id="gutenberg-editor" class="editor-mode" style="display: none;">
                <div class="gutenberg-info">
                    <h3><?php _e('Gutenberg Block Templates', 'kotacom-ai'); ?></h3>
                    <p><?php _e('Use the WordPress block editor to create templates with AI content blocks.', 'kotacom-ai'); ?></p>
                    
                    <div class="available-blocks">
                        <h4><?php _e('Available AI Blocks:', 'kotacom-ai'); ?></h4>
                        <ul>
                            <li><strong><?php _e('AI Content Block', 'kotacom-ai'); ?></strong> - <?php _e('Generate AI content inline', 'kotacom-ai'); ?></li>
                            <li><strong><?php _e('AI Template Structure', 'kotacom-ai'); ?></strong> - <?php _e('Define template layout', 'kotacom-ai'); ?></li>
                            <li><strong><?php _e('AI Section Block', 'kotacom-ai'); ?></strong> - <?php _e('Create structured sections', 'kotacom-ai'); ?></li>
                            <li><strong><?php _e('AI List Block', 'kotacom-ai'); ?></strong> - <?php _e('Generate AI-powered lists', 'kotacom-ai'); ?></li>
                        </ul>
                    </div>
                    
                    <a href="<?php echo admin_url('post-new.php?post_type=kotacom_template'); ?>" class="button button-primary">
                        <?php _e('Open Gutenberg Editor', 'kotacom-ai'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Preview Area -->
        <div id="template-preview" class="template-preview" style="display: none;">
            <div class="preview-header">
                <h3><?php _e('Template Preview', 'kotacom-ai'); ?></h3>
                <div class="preview-controls">
                    <label>
                        <?php _e('Preview Keyword:', 'kotacom-ai'); ?>
                        <input type="text" id="preview-keyword" value="WordPress" placeholder="Enter keyword for preview">
                    </label>
                    <button id="refresh-preview" class="button"><?php _e('Refresh', 'kotacom-ai'); ?></button>
                </div>
            </div>
            <div class="preview-content">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Shortcode Builder Modal -->
<div id="shortcode-builder-modal" class="kotacom-modal" style="display: none;">
    <div class="kotacom-modal-content">
        <span class="kotacom-modal-close">&times;</span>
        <h2><?php _e('Shortcode Builder', 'kotacom-ai'); ?></h2>
        
        <form id="shortcode-builder-form">
            <!-- Form fields will be dynamically generated -->
        </form>
        
        <div class="modal-actions">
            <button id="insert-shortcode" class="button button-primary"><?php _e('Insert Shortcode', 'kotacom-ai'); ?></button>
            <button id="cancel-shortcode" class="button"><?php _e('Cancel', 'kotacom-ai'); ?></button>
        </div>
    </div>
</div>

<style>
/* Add new styles for button-danger and separator */
.button-danger {
    background: #dc3232 !important;
    border-color: #dc3232 !important;
    color: #fff !important;
}
.button-danger:hover {
    background: #e04b4b !important;
    border-color: #e04b4b !important;
}
hr.ai-separator {
    border: 0;
    height: 1px;
    background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0));
    margin: 20px 0;
}

/* Existing styles (ensure they are present) */
.template-editor-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 20px;
}

.template-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ccd0d4;
    background: #f9f9f9;
}

.template-info {
    display: flex;
    gap: 15px;
    align-items: center;
}

.template-actions {
    display: flex;
    gap: 10px;
}

.builder-layout {
    display: grid;
    grid-template-columns: 250px 1fr 300px;
    min-height: 600px;
}

.component-palette {
    background: #f9f9f9;
    border-right: 1px solid #ccd0d4;
    padding: 20px;
}

.component-group {
    margin-bottom: 20px;
}

.component-group h4 {
    margin: 0 0 10px 0;
    font-size: 12px;
    text-transform: uppercase;
    color: #666;
}

.template-component {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    margin-bottom: 5px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: grab; /* Changed to grab */
    transition: all 0.2s;
}

.template-component:hover {
    background: #f0f0f1;
    border-color: #0073aa;
}

.template-component.dragging { /* Style for when dragging */
    opacity: 0.5;
    border: 1px dashed #0073aa;
}

.component-icon {
    font-size: 16px;
}

.component-label {
    font-size: 12px;
    font-weight: 500;
}

.template-builder-area {
    padding: 20px;
}

.builder-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.builder-tools {
    display: flex;
    gap: 10px;
    flex-wrap: wrap; /* Allow buttons to wrap */
}

.template-builder {
    min-height: 400px;
    border: 2px dashed #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    background: #fefefe; /* Lighter background for builder area */
}

.builder-placeholder {
    text-align: center;
    color: #666;
    padding: 60px 20px;
}

.section-placeholder { /* Style for sortable placeholder */
    border: 2px dashed #0073aa;
    background: #e0f2f7;
    height: 50px;
    margin-bottom: 15px;
    border-radius: 4px;
}

.template-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
    overflow: hidden;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    background: #f9f9f9;
    border-bottom: 1px solid #ddd;
}

.section-handle {
    cursor: grab; /* Changed to grab */
    color: #666;
    font-weight: bold;
    font-size: 18px;
    line-height: 1;
}

.section-title {
    flex: 1;
    font-weight: 500;
}

.section-actions {
    display: flex;
    gap: 5px;
}

.section-actions button {
    padding: 2px 8px;
    font-size: 11px;
    border: 1px solid #0073aa; /* Added border */
    background: #0073aa;
    color: white;
    border-radius: 3px; /* Slightly larger radius */
    cursor: pointer;
    transition: all 0.2s;
}

.section-actions button:hover {
    background: #005177;
    border-color: #005177;
}

.section-content {
    padding: 15px;
}

.component-preview {
    background: #f9f9f9;
    padding: 10px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 12px;
    margin-bottom: 10px;
    white-space: pre-wrap; /* Preserve whitespace and wrap */
    word-break: break-all; /* Break long words */
}

.component-settings {
    display: grid;
    gap: 10px;
    padding-top: 10px; /* Add some padding */
    border-top: 1px dashed #eee; /* Separator */
}

.component-settings label {
    font-weight: 500;
    margin-bottom: 3px;
    display: block;
    font-size: 13px; /* Slightly larger font */
}

.component-settings input[type="text"],
.component-settings input[type="number"],
.component-settings select,
.component-settings textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box; /* Include padding in width */
}

.properties-panel {
    background: #f9f9f9;
    border-left: 1px solid #ccd0d4;
    padding: 20px;
}

.property-group {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ddd;
}

.property-group:last-child {
    border-bottom: none; /* No border for the last group */
}

.property-group h4 {
    margin: 0 0 10px 0;
    font-size: 13px;
    color: #333;
}

.property-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 12px;
}

.variable-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto; /* Added column for description */
    gap: 5px;
    margin-bottom: 8px;
    align-items: center;
}

.variable-row input {
    font-size: 11px;
    padding: 4px 6px;
}

.remove-variable {
    padding: 4px 8px;
    font-size: 10px;
    background: #dc3232;
    color: white;
    border: none;
    border-radius: 2px;
    cursor: pointer;
}

.shortcode-editor-layout {
    padding: 20px;
}

.editor-toolbar {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ddd;
}

.editor-toolbar button {
    margin-right: 10px;
}

.editor-content textarea {
    min-height: 400px; /* Ensure enough height */
}

.editor-help {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.shortcode-examples {
    display: grid;
    gap: 15px;
}

.shortcode-example {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
}

.shortcode-example code {
    display: block;
    background: #333;
    color: #fff;
    padding: 8px 12px;
    border-radius: 3px;
    margin-bottom: 8px;
    font-size: 12px;
    white-space: pre-wrap; /* Preserve whitespace and wrap */
    word-break: break-all; /* Break long words */
}

.shortcode-example p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

.template-preview {
    border-top: 1px solid #ccd0d4;
    background: #fff;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f9f9f9;
    border-bottom: 1px solid #ddd;
}

.preview-controls {
    display: flex;
    align-items: center;
    gap: 15px;
}

.preview-controls label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.preview-content {
    padding: 20px;
    min-height: 300px;
}

.gutenberg-info {
    padding: 40px;
    text-align: center;
}

.available-blocks {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 4px;
    margin: 20px 0;
    text-align: left;
}

.available-blocks ul {
    margin: 10px 0;
}

.available-blocks li {
    margin-bottom: 8px;
}

/* Modal Styles */
.kotacom-modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 100000; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}

.kotacom-modal-content {
    background-color: #fefefe;
    margin: 10% auto; /* 10% from the top and centered */
    padding: 20px;
    border: 1px solid #888;
    width: 80%; /* Could be more or less, depending on screen size */
    max-width: 600px;
    border-radius: 8px;
    position: relative;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.kotacom-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.kotacom-modal-close:hover,
.kotacom-modal-close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.kotacom-modal-content h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.kotacom-modal-content .form-group {
    margin-bottom: 15px;
}

.kotacom-modal-content .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.kotacom-modal-content .form-group input[type="text"],
.kotacom-modal-content .form-group input[type="number"],
.kotacom-modal-content .form-group select,
.kotacom-modal-content .form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.kotacom-modal-content .modal-actions {
    margin-top: 20px;
    text-align: right;
    border-top: 1px solid #eee;
    padding-top: 15px;
}

.kotacom-modal-content .modal-actions button {
    margin-left: 10px;
}


/* Responsive Design */
@media (max-width: 1200px) {
    .builder-layout {
        grid-template-columns: 200px 1fr 250px;
    }
}

@media (max-width: 768px) {
    .builder-layout {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto auto;
    }
    
    .template-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .template-info {
        flex-direction: column;
    }

    .component-palette, .properties-panel {
        border-right: none;
        border-left: none;
        border-bottom: 1px solid #ccd0d4;
    }
}
</style>

</div><!-- END #template-editor -->
