<?php
/**
 * Prompt Templates admin page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Prompt Templates', 'kotacom-ai'); ?></h1>
    
    <div class="kotacom-ai-prompts">
        <!-- Add Prompt Section -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Add New Template', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <form id="add-prompt-form">
                    <?php wp_nonce_field('kotacom_ai_nonce', 'kotacom_ai_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Template Name', 'kotacom-ai'); ?></th>
                            <td>
                                <input type="text" name="prompt_name" id="prompt-name-input" class="regular-text" required>
                                <p class="description"><?php _e('A descriptive name for this template.', 'kotacom-ai'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Prompt Template', 'kotacom-ai'); ?></th>
                            <td>
                                <textarea name="prompt_template" id="prompt-template-input" rows="8" class="large-text" required placeholder="<?php _e('Enter your prompt template here. Use {keyword} as placeholder.', 'kotacom-ai'); ?>"></textarea>
                                <p class="description">
                                    <?php _e('Use {keyword} as a placeholder that will be replaced with the actual keyword during content generation.', 'kotacom-ai'); ?>
                                    <br>
                                    <?php _e('Example: "Write a comprehensive article about {keyword} that covers the main benefits and features."', 'kotacom-ai'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Description', 'kotacom-ai'); ?></th>
                            <td>
                                <textarea name="description" id="description-input" rows="3" class="large-text" placeholder="<?php _e('Optional description of what this template is used for.', 'kotacom-ai'); ?>"></textarea>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Add Template', 'kotacom-ai'); ?></button>
                        <span class="spinner"></span>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Prompts List Section -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Existing Templates', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <div id="prompts-table-container">
                    <table class="wp-list-table widefat fixed striped" id="prompts-table">
                        <thead>
                            <tr>
                                <th><?php _e('Template Name', 'kotacom-ai'); ?></th>
                                <th><?php _e('Description', 'kotacom-ai'); ?></th>
                                <th><?php _e('Created', 'kotacom-ai'); ?></th>
                                <th><?php _e('Actions', 'kotacom-ai'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="prompts-table-body">
                            <!-- Prompts will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Prompt Modal -->
<div id="edit-prompt-modal" class="kotacom-modal" style="display: none;">
    <div class="kotacom-modal-content">
        <span class="kotacom-modal-close">&times;</span>
        <h2><?php _e('Edit Prompt Template', 'kotacom-ai'); ?></h2>
        <form id="edit-prompt-form">
            <input type="hidden" name="prompt_id" id="edit-prompt-id">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Template Name', 'kotacom-ai'); ?></th>
                    <td>
                        <input type="text" name="prompt_name" id="edit-prompt-name" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Prompt Template', 'kotacom-ai'); ?></th>
                    <td>
                        <textarea name="prompt_template" id="edit-prompt-template" rows="8" class="large-text" required></textarea>
                        <p class="description"><?php _e('Use {keyword} as placeholder for the keyword.', 'kotacom-ai'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Description', 'kotacom-ai'); ?></th>
                    <td>
                        <textarea name="description" id="edit-description" rows="3" class="large-text"></textarea>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Update Template', 'kotacom-ai'); ?></button>
                <button type="button" class="button" onclick="closeModal()"><?php _e('Cancel', 'kotacom-ai'); ?></button>
                <span class="spinner"></span>
            </p>
        </form>
    </div>
</div>

<!-- View Prompt Modal -->
<div id="view-prompt-modal" class="kotacom-modal" style="display: none;">
    <div class="kotacom-modal-content">
        <span class="kotacom-modal-close">&times;</span>
        <h2 id="view-prompt-title"><?php _e('View Prompt Template', 'kotacom-ai'); ?></h2>
        <div class="prompt-details">
            <h3><?php _e('Template Content:', 'kotacom-ai'); ?></h3>
            <div id="view-prompt-content" class="prompt-content"></div>
            
            <h3><?php _e('Description:', 'kotacom-ai'); ?></h3>
            <div id="view-prompt-description" class="prompt-description"></div>
        </div>
        <p class="submit">
            <button type="button" class="button" onclick="closeModal()"><?php _e('Close', 'kotacom-ai'); ?></button>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Add prompt form
    $('#add-prompt-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var $spinner = $form.find('.spinner');
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_add_prompt',
                nonce: kotacomAI.nonce,
                prompt_name: $('#prompt-name-input').val(),
                prompt_template: $('#prompt-template-input').val(),
                description: $('#description-input').val()
            },
            success: function(response) {
                if (response.success) {
                    $form[0].reset();
                    loadPrompts();
                    showNotice(response.data.message, 'success');
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Edit prompt form
    $('#edit-prompt-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var $spinner = $form.find('.spinner');
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_update_prompt',
                nonce: kotacomAI.nonce,
                id: $('#edit-prompt-id').val(),
                prompt_name: $('#edit-prompt-name').val(),
                prompt_template: $('#edit-prompt-template').val(),
                description: $('#edit-description').val()
            },
            success: function(response) {
                if (response.success) {
                    closeModal();
                    loadPrompts();
                    showNotice(response.data.message, 'success');
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Load initial prompts
    loadPrompts();
    
    function loadPrompts() {
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_get_prompts',
                nonce: kotacomAI.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayPrompts(response.data.prompts);
                }
            }
        });
    }
    
    function displayPrompts(prompts) {
        var html = '';
        
        if (prompts.length === 0) {
            html = '<tr><td colspan="4"><?php _e('No prompt templates found.', 'kotacom-ai'); ?></td></tr>';
        } else {
            $.each(prompts, function(index, prompt) {
                html += '<tr>';
                html += '<td><strong>' + prompt.prompt_name + '</strong></td>';
                html += '<td>' + (prompt.description || '<?php _e('No description', 'kotacom-ai'); ?>') + '</td>';
                html += '<td>' + prompt.created_at + '</td>';
                html += '<td>';
                html += '<button type="button" class="button button-small view-prompt" data-id="' + prompt.id + '" data-name="' + prompt.prompt_name + '" data-template="' + prompt.prompt_template + '" data-description="' + (prompt.description || '') + '"><?php _e('View', 'kotacom-ai'); ?></button> ';
                html += '<button type="button" class="button button-small edit-prompt" data-id="' + prompt.id + '" data-name="' + prompt.prompt_name + '" data-template="' + prompt.prompt_template + '" data-description="' + (prompt.description || '') + '"><?php _e('Edit', 'kotacom-ai'); ?></button> ';
                html += '<button type="button" class="button button-small delete-prompt" data-id="' + prompt.id + '"><?php _e('Delete', 'kotacom-ai'); ?></button>';
                html += '</td>';
                html += '</tr>';
            });
        }
        
        $('#prompts-table-body').html(html);
        
        // Bind events
        $('.view-prompt').on('click', openViewModal);
        $('.edit-prompt').on('click', openEditModal);
        $('.delete-prompt').on('click', deletePrompt);
    }
    
    function openViewModal() {
        var name = $(this).data('name');
        var template = $(this).data('template');
        var description = $(this).data('description');
        
        $('#view-prompt-title').text(name);
        $('#view-prompt-content').text(template);
        $('#view-prompt-description').text(description || '<?php _e('No description provided.', 'kotacom-ai'); ?>');
        
        $('#view-prompt-modal').show();
    }
    
    function openEditModal() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var template = $(this).data('template');
        var description = $(this).data('description');
        
        $('#edit-prompt-id').val(id);
        $('#edit-prompt-name').val(name);
        $('#edit-prompt-template').val(template);
        $('#edit-description').val(description);
        
        $('#edit-prompt-modal').show();
    }
    
    function deletePrompt() {
        var id = $(this).data('id');
        
        if (confirm(kotacomAI.strings.confirm_delete)) {
            $.ajax({
                url: kotacomAI.ajaxurl,
                type: 'POST',
                data: {
                    action: 'kotacom_delete_prompt',
                    nonce: kotacomAI.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        loadPrompts();
                        showNotice(response.data.message, 'success');
                    } else {
                        showNotice(response.data.message, 'error');
                    }
                }
            });
        }
    }
    
    function showNotice(message, type) {
        var html = '<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>';
        $('.wrap h1').after(html);
        
        setTimeout(function() {
            $('.notice').fadeOut();
        }, 5000);
    }
    
    // Modal functions
    window.closeModal = function() {
        $('.kotacom-modal').hide();
    };
    
    $('.kotacom-modal-close').on('click', closeModal);
    
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('kotacom-modal')) {
            closeModal();
        }
    });
});
</script>
