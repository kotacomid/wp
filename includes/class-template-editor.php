<?php
/**
 * Template Editor class for managing AI content templates
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Template_Editor {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the template editor
     */
    public function init() {
        // Add hooks if needed
        add_action('wp_ajax_kotacom_save_template_editor', array($this, 'ajax_save_template'));
        add_action('wp_ajax_kotacom_load_template_editor', array($this, 'ajax_load_template'));
    }
    
    /**
     * Render template editor interface
     */
    public function render_editor($template_id = null) {
        $template_data = null;
        
        if ($template_id) {
            $template_data = $this->get_template($template_id);
        }
        
        ob_start();
        ?>
        <div class="kotacom-template-editor">
            <div class="template-editor-header">
                <h3><?php echo $template_id ? __('Edit Template', 'kotacom-ai') : __('Create New Template', 'kotacom-ai'); ?></h3>
            </div>
            
            <div class="template-editor-form">
                <form id="template-editor-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="template-name"><?php _e('Template Name', 'kotacom-ai'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="template-name" 
                                       name="template_name" 
                                       class="regular-text" 
                                       value="<?php echo $template_data ? esc_attr($template_data['name']) : ''; ?>" 
                                       placeholder="<?php _e('Enter template name', 'kotacom-ai'); ?>" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="template-description"><?php _e('Description', 'kotacom-ai'); ?></label>
                            </th>
                            <td>
                                <textarea id="template-description" 
                                         name="template_description" 
                                         class="large-text" 
                                         rows="3" 
                                         placeholder="<?php _e('Template description (optional)', 'kotacom-ai'); ?>"><?php echo $template_data ? esc_textarea($template_data['description']) : ''; ?></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="template-content"><?php _e('Template Content', 'kotacom-ai'); ?></label>
                            </th>
                            <td>
                                <div class="template-content-editor">
                                    <?php
                                    $content = $template_data ? $template_data['content'] : '';
                                    wp_editor($content, 'template_content', array(
                                        'textarea_name' => 'template_content',
                                        'media_buttons' => true,
                                        'textarea_rows' => 15,
                                        'teeny' => false,
                                        'tinymce' => array(
                                            'plugins' => 'wordpress,wplink,wpdialogs'
                                        )
                                    ));
                                    ?>
                                </div>
                                <p class="description">
                                    <?php _e('Use {keyword} as placeholder for the keyword in your template.', 'kotacom-ai'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="template-tags"><?php _e('Tags', 'kotacom-ai'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="template-tags" 
                                       name="template_tags" 
                                       class="regular-text" 
                                       value="<?php echo $template_data ? esc_attr($template_data['tags']) : ''; ?>" 
                                       placeholder="<?php _e('blog, article, product (comma separated)', 'kotacom-ai'); ?>" />
                                <p class="description"><?php _e('Add tags to organize your templates (comma separated)', 'kotacom-ai'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php echo $template_id ? __('Update Template', 'kotacom-ai') : __('Save Template', 'kotacom-ai'); ?>
                        </button>
                        <button type="button" class="button" onclick="window.history.back();">
                            <?php _e('Cancel', 'kotacom-ai'); ?>
                        </button>
                    </p>
                    
                    <?php if ($template_id): ?>
                        <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>" />
                    <?php endif; ?>
                    <?php wp_nonce_field('kotacom_ai_nonce', 'nonce'); ?>
                </form>
            </div>
            
            <div class="template-preview">
                <h4><?php _e('Template Preview', 'kotacom-ai'); ?></h4>
                <div id="template-preview-content">
                    <?php _e('Preview will appear here when you enter content above.', 'kotacom-ai'); ?>
                </div>
                <button type="button" class="button" id="preview-template">
                    <?php _e('Preview with Sample Keyword', 'kotacom-ai'); ?>
                </button>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Template form submission
            $('#template-editor-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'kotacom_save_template_editor',
                    template_name: $('#template-name').val(),
                    template_description: $('#template-description').val(),
                    template_content: tinyMCE.get('template_content').getContent(),
                    template_tags: $('#template-tags').val(),
                    nonce: $('input[name="nonce"]').val()
                };
                
                if ($('input[name="template_id"]').length) {
                    formData.template_id = $('input[name="template_id"]').val();
                }
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        alert('<?php _e('Template saved successfully!', 'kotacom-ai'); ?>');
                        if (!formData.template_id) {
                            // Redirect to edit mode for new templates
                            window.location.href = window.location.href + '&template_id=' + response.data.template_id;
                        }
                    } else {
                        alert('<?php _e('Error:', 'kotacom-ai'); ?> ' + response.data.message);
                    }
                });
            });
            
            // Preview functionality
            $('#preview-template').on('click', function() {
                var content = tinyMCE.get('template_content').getContent();
                var sampleKeyword = 'sample keyword';
                var previewContent = content.replace(/{keyword}/g, sampleKeyword);
                $('#template-preview-content').html(previewContent);
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get template data by ID
     */
    public function get_template($template_id) {
        global $wpdb;
        
        // This assumes you have a templates table, adjust as needed
        $table_name = $wpdb->prefix . 'kotacom_templates';
        
        $template = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $template_id
            ),
            ARRAY_A
        );
        
        return $template;
    }
    
    /**
     * AJAX handler for saving templates
     */
    public function ajax_save_template() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $template_name = sanitize_text_field($_POST['template_name'] ?? '');
        $template_description = sanitize_textarea_field($_POST['template_description'] ?? '');
        $template_content = wp_kses_post($_POST['template_content'] ?? '');
        $template_tags = sanitize_text_field($_POST['template_tags'] ?? '');
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if (empty($template_name) || empty($template_content)) {
            wp_send_json_error(array('message' => __('Template name and content are required', 'kotacom-ai')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'kotacom_templates';
        
        $data = array(
            'name' => $template_name,
            'description' => $template_description,
            'content' => $template_content,
            'tags' => $template_tags,
            'updated_at' => current_time('mysql')
        );
        
        if ($template_id) {
            // Update existing template
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $template_id),
                array('%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => __('Template updated successfully', 'kotacom-ai')));
            } else {
                wp_send_json_error(array('message' => __('Failed to update template', 'kotacom-ai')));
            }
        } else {
            // Create new template
            $data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert(
                $table_name,
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Template created successfully', 'kotacom-ai'),
                    'template_id' => $wpdb->insert_id
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to create template', 'kotacom-ai')));
            }
        }
    }
    
    /**
     * AJAX handler for loading templates
     */
    public function ajax_load_template() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kotacom-ai')));
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if (!$template_id) {
            wp_send_json_error(array('message' => __('Template ID is required', 'kotacom-ai')));
        }
        
        $template = $this->get_template($template_id);
        
        if ($template) {
            wp_send_json_success($template);
        } else {
            wp_send_json_error(array('message' => __('Template not found', 'kotacom-ai')));
        }
    }
}