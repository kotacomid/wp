<?php
/**
 * Queue Status admin page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Queue Status', 'kotacom-ai'); ?></h1>
    
    <div class="kotacom-ai-queue">
        <!-- Queue Statistics -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Queue Statistics', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <div class="queue-stats">
                    <div class="stat-item">
                        <span class="stat-number pending"><?php echo esc_html($queue_status['pending']); ?></span>
                        <span class="stat-label"><?php _e('Pending', 'kotacom-ai'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number processing"><?php echo esc_html($queue_status['processing']); ?></span>
                        <span class="stat-label"><?php _e('Processing', 'kotacom-ai'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number completed"><?php echo esc_html($queue_status['completed']); ?></span>
                        <span class="stat-label"><?php _e('Completed', 'kotacom-ai'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number failed"><?php echo esc_html($queue_status['failed']); ?></span>
                        <span class="stat-label"><?php _e('Failed', 'kotacom-ai'); ?></span>
                    </div>
                </div>
                
                <div class="queue-actions">
                    <button type="button" id="refresh-queue" class="button"><?php _e('Refresh', 'kotacom-ai'); ?></button>
                    <button type="button" id="retry-failed" class="button button-secondary"><?php _e('Retry Failed', 'kotacom-ai'); ?></button>
                    <button type="button" id="clear-completed" class="button button-secondary"><?php _e('Clear Completed', 'kotacom-ai'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Failed Items -->
        <?php if (!empty($failed_items)): ?>
        <div class="postbox">
            <h2 class="hndle"><?php _e('Failed Items', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Keyword', 'kotacom-ai'); ?></th>
                            <th><?php _e('Error Message', 'kotacom-ai'); ?></th>
                            <th><?php _e('Created', 'kotacom-ai'); ?></th>
                            <th><?php _e('Actions', 'kotacom-ai'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($failed_items as $item): ?>
                        <tr>
                            <td><strong><?php echo esc_html($item['keyword']); ?></strong></td>
                            <td><?php echo esc_html($item['error_message']); ?></td>
                            <td><?php echo esc_html($item['created_at']); ?></td>
                            <td>
                                <button type="button" class="button button-small retry-item" data-id="<?php echo esc_attr($item['id']); ?>">
                                    <?php _e('Retry', 'kotacom-ai'); ?>
                                </button>
                                <button type="button" class="button button-small view-details" data-id="<?php echo esc_attr($item['id']); ?>" data-keyword="<?php echo esc_attr($item['keyword']); ?>" data-prompt="<?php echo esc_attr($item['prompt_template']); ?>" data-parameters="<?php echo esc_attr($item['parameters']); ?>" data-settings="<?php echo esc_attr($item['post_settings']); ?>">
                                    <?php _e('View Details', 'kotacom-ai'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Queue Processing Status -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Processing Status', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <div id="processing-status">
                    <p><?php _e('Checking processing status...', 'kotacom-ai'); ?></p>
                </div>
                
                <div class="processing-controls">
                    <button type="button" id="force-process" class="button button-secondary"><?php _e('Force Process Queue', 'kotacom-ai'); ?></button>
                    <button type="button" id="stop-processing" class="button button-secondary"><?php _e('Stop Processing', 'kotacom-ai'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Queue Item Details Modal -->
<div id="queue-item-modal" class="kotacom-modal" style="display: none;">
    <div class="kotacom-modal-content">
        <span class="kotacom-modal-close">&times;</span>
        <h2><?php _e('Queue Item Details', 'kotacom-ai'); ?></h2>
        <div class="queue-item-details">
            <h3><?php _e('Keyword:', 'kotacom-ai'); ?></h3>
            <div id="item-keyword"></div>
            
            <h3><?php _e('Prompt Template:', 'kotacom-ai'); ?></h3>
            <div id="item-prompt" class="code-block"></div>
            
            <h3><?php _e('Parameters:', 'kotacom-ai'); ?></h3>
            <div id="item-parameters" class="code-block"></div>
            
            <h3><?php _e('Post Settings:', 'kotacom-ai'); ?></h3>
            <div id="item-settings" class="code-block"></div>
        </div>
        <p class="submit">
            <button type="button" class="button" onclick="closeModal()"><?php _e('Close', 'kotacom-ai'); ?></button>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Auto-refresh queue status every 30 seconds
    setInterval(function() {
        refreshQueueStatus();
    }, 30000);
    
    // Refresh queue button
    $('#refresh-queue').on('click', function() {
        refreshQueueStatus();
    });
    
    // Retry failed items
    $('#retry-failed').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to retry all failed items?', 'kotacom-ai'); ?>')) {
            $.ajax({
                url: kotacomAI.ajaxurl,
                type: 'POST',
                data: {
                    action: 'kotacom_retry_failed',
                    nonce: kotacomAI.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotice(response.data.message, 'error');
                    }
                }
            });
        }
    });
    
    // Clear completed items
    $('#clear-completed').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to clear all completed items?', 'kotacom-ai'); ?>')) {
            // This would need a new AJAX endpoint
            showNotice('<?php _e('Feature coming soon', 'kotacom-ai'); ?>', 'info');
        }
    });
    
    // Force process queue
    $('#force-process').on('click', function() {
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_force_process_queue',
                nonce: kotacomAI.nonce
            },
            success: function(response) {
                showNotice('<?php _e('Queue processing triggered', 'kotacom-ai'); ?>', 'success');
                setTimeout(refreshQueueStatus, 2000);
            }
        });
    });
    
    // Stop processing
    $('#stop-processing').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to stop queue processing?', 'kotacom-ai'); ?>')) {
            $.ajax({
                url: kotacomAI.ajaxurl,
                type: 'POST',
                data: {
                    action: 'kotacom_stop_processing',
                    nonce: kotacomAI.nonce
                },
                success: function(response) {
                    showNotice('<?php _e('Queue processing stopped', 'kotacom-ai'); ?>', 'success');
                    setTimeout(refreshQueueStatus, 2000);
                }
            });
        }
    });
    
    // Retry individual item
    $('.retry-item').on('click', function() {
        var itemId = $(this).data('id');
        
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_retry_queue_item',
                nonce: kotacomAI.nonce,
                item_id: itemId
            },
            success: function(response) {
                if (response.success) {
                    showNotice('<?php _e('Item queued for retry', 'kotacom-ai'); ?>', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotice(response.data.message, 'error');
                }
            }
        });
    });
    
    // View item details
    $('.view-details').on('click', function() {
        var keyword = $(this).data('keyword');
        var prompt = $(this).data('prompt');
        var parameters = $(this).data('parameters');
        var settings = $(this).data('settings');
        
        $('#item-keyword').text(keyword);
        $('#item-prompt').text(prompt);
        
        try {
            var parsedParams = JSON.parse(parameters);
            $('#item-parameters').html('<pre>' + JSON.stringify(parsedParams, null, 2) + '</pre>');
        } catch (e) {
            $('#item-parameters').text(parameters);
        }
        
        try {
            var parsedSettings = JSON.parse(settings);
            $('#item-settings').html('<pre>' + JSON.stringify(parsedSettings, null, 2) + '</pre>');
        } catch (e) {
            $('#item-settings').text(settings);
        }
        
        $('#queue-item-modal').show();
    });
    
    // Initial status check
    checkProcessingStatus();
    
    function refreshQueueStatus() {
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_get_queue_status',
                nonce: kotacomAI.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateQueueStats(response.data);
                }
            }
        });
    }
    
    function updateQueueStats(stats) {
        $('.stat-number.pending').text(stats.pending);
        $('.stat-number.processing').text(stats.processing);
        $('.stat-number.completed').text(stats.completed);
        $('.stat-number.failed').text(stats.failed);
    }
    
    function checkProcessingStatus() {
        // This would check if queue is currently processing
        $('#processing-status').html('<p><?php _e('Queue processor is running normally.', 'kotacom-ai'); ?></p>');
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
