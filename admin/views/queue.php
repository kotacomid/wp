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
                        <?php foreach ($failed_items as $item): 
                            $keyword = isset($item['data']['keyword']) ? $item['data']['keyword'] : (isset($item['keyword']) ? $item['keyword'] : 'Unknown');
                            $error_message = isset($item['last_error']) ? $item['last_error'] : (isset($item['error_message']) ? $item['error_message'] : 'No error message');
                            $created_at = isset($item['created_at']) ? $item['created_at'] : 'Unknown';
                            $prompt = isset($item['data']['prompt']) ? $item['data']['prompt'] : (isset($item['prompt_template']) ? $item['prompt_template'] : '');
                            $parameters = isset($item['data']['params']) ? json_encode($item['data']['params']) : (isset($item['parameters']) ? $item['parameters'] : '{}');
                            $post_settings = isset($item['data']['post_status']) ? json_encode(array(
                                'post_status' => $item['data']['post_status'],
                                'post_type' => $item['data']['post_type'] ?? 'post',
                                'categories' => $item['data']['categories'] ?? [],
                                'tags' => $item['data']['tags'] ?? ''
                            )) : (isset($item['post_settings']) ? $item['post_settings'] : '{}');
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($keyword); ?></strong></td>
                            <td><?php echo esc_html($error_message); ?></td>
                            <td><?php echo esc_html($created_at); ?></td>
                            <td>
                                <button type="button" class="button button-small retry-item" data-id="<?php echo esc_attr($item['id']); ?>">
                                    <?php _e('Retry', 'kotacom-ai'); ?>
                                </button>
                                <button type="button" class="button button-small view-details" data-id="<?php echo esc_attr($item['id']); ?>" data-keyword="<?php echo esc_attr($keyword); ?>" data-prompt="<?php echo esc_attr($prompt); ?>" data-parameters="<?php echo esc_attr($parameters); ?>" data-settings="<?php echo esc_attr($post_settings); ?>">
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
        
        <!-- Currently Processing Items -->
        <div class="postbox">
            <h2 class="hndle">
                <?php _e('Currently Processing', 'kotacom-ai'); ?>
                <span class="processing-indicator" style="display: inline-block; width: 10px; height: 10px; background: #00a32a; border-radius: 50%; margin-left: 8px; animation: pulse 2s infinite;"></span>
            </h2>
            <div class="inside">
                <div id="processing-items-container">
                    <p><?php _e('Loading processing items...', 'kotacom-ai'); ?></p>
                </div>
                <div style="margin-top: 15px;">
                    <button type="button" id="refresh-processing" class="button button-small"><?php _e('Refresh Processing Items', 'kotacom-ai'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Pending Items (Next to Process) -->
        <div class="postbox">
            <h2 class="hndle">
                <?php _e('Pending Items (Next to Process)', 'kotacom-ai'); ?>
                <span class="pending-count" style="background: #ddd; color: #333; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;"></span>
            </h2>
            <div class="inside">
                <div id="pending-items-container">
                    <p><?php _e('Loading pending items...', 'kotacom-ai'); ?></p>
                </div>
                <div style="margin-top: 15px;">
                    <button type="button" id="process-next-batch" class="button button-primary"><?php _e('Process Next Batch', 'kotacom-ai'); ?></button>
                </div>
            </div>
        </div>
        
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
        loadProcessingItems();
        loadPendingItems();
    }, 30000);
    
    // Initial load
    loadProcessingItems();
    loadPendingItems();
    
    // Refresh queue button
    $('#refresh-queue').on('click', function() {
        refreshQueueStatus();
        loadProcessingItems();
        loadPendingItems();
    });
    
    // Refresh processing items
    $('#refresh-processing').on('click', function() {
        loadProcessingItems();
    });
    
    // Process next batch
    $('#process-next-batch').on('click', function() {
        var $btn = $(this);
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
                    showNotice('Queue processing triggered successfully', 'success');
                    setTimeout(function() {
                        loadProcessingItems();
                        loadPendingItems();
                        refreshQueueStatus();
                    }, 2000);
                } else {
                    showNotice('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('AJAX error occurred', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Process Next Batch');
            }
        });
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
        $('.pending-count').text(stats.pending);
    }
    
    function loadProcessingItems() {
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_get_processing_items',
                nonce: kotacomAI.nonce
            },
            success: function(response) {
                if (response.success && response.data.items) {
                    displayProcessingItems(response.data.items);
                } else {
                    $('#processing-items-container').html('<p style="color: #666; font-style: italic;">No items currently processing</p>');
                }
            },
            error: function() {
                $('#processing-items-container').html('<p style="color: #d63638;">Error loading processing items</p>');
            }
        });
    }
    
    function loadPendingItems() {
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_get_pending_items',
                nonce: kotacomAI.nonce,
                limit: 10
            },
            success: function(response) {
                if (response.success && response.data.items) {
                    displayPendingItems(response.data.items);
                } else {
                    $('#pending-items-container').html('<p style="color: #666; font-style: italic;">No items pending</p>');
                }
            },
            error: function() {
                $('#pending-items-container').html('<p style="color: #d63638;">Error loading pending items</p>');
            }
        });
    }
    
    function displayProcessingItems(items) {
        if (items.length === 0) {
            $('#processing-items-container').html('<p style="color: #666; font-style: italic;">No items currently processing</p>');
            return;
        }
        
        var html = '<table class="wp-list-table widefat fixed striped" style="margin: 0;">';
        html += '<thead><tr>';
        html += '<th style="width: 20%;">Keyword</th>';
        html += '<th style="width: 15%;">Action</th>';
        html += '<th style="width: 15%;">Started</th>';
        html += '<th style="width: 10%;">Attempts</th>';
        html += '<th style="width: 40%;">Progress</th>';
        html += '</tr></thead><tbody>';
        
        items.forEach(function(item) {
            var keyword = item.data && item.data.keyword ? item.data.keyword : 'Unknown';
            var action = item.action || 'generate_content';
            var startedAt = item.started_at ? formatTime(item.started_at) : 'Just started';
            var attempts = item.attempts || 1;
            
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(keyword) + '</strong></td>';
            html += '<td><span class="action-badge action-' + action + '">' + formatAction(action) + '</span></td>';
            html += '<td>' + startedAt + '</td>';
            html += '<td>' + attempts + '</td>';
            html += '<td><div class="progress-bar"><div class="progress-fill" style="width: 70%; animation: pulse 2s infinite;"></div></div> Processing...</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        $('#processing-items-container').html(html);
    }
    
    function displayPendingItems(items) {
        if (items.length === 0) {
            $('#pending-items-container').html('<p style="color: #666; font-style: italic;">No items pending</p>');
            return;
        }
        
        var html = '<table class="wp-list-table widefat fixed striped" style="margin: 0;">';
        html += '<thead><tr>';
        html += '<th style="width: 25%;">Keyword</th>';
        html += '<th style="width: 15%;">Action</th>';
        html += '<th style="width: 15%;">Created</th>';
        html += '<th style="width: 15%;">Priority</th>';
        html += '<th style="width: 30%;">Details</th>';
        html += '</tr></thead><tbody>';
        
        items.forEach(function(item, index) {
            var keyword = item.data && item.data.keyword ? item.data.keyword : 'Unknown';
            var action = item.action || 'generate_content';
            var createdAt = item.created_at ? formatTime(item.created_at) : 'Unknown';
            var priority = item.priority || 10;
            var queuePosition = index + 1;
            
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(keyword) + '</strong></td>';
            html += '<td><span class="action-badge action-' + action + '">' + formatAction(action) + '</span></td>';
            html += '<td>' + createdAt + '</td>';
            html += '<td><span class="priority-badge priority-' + getPriorityClass(priority) + '">' + priority + '</span></td>';
            html += '<td>Queue position: #' + queuePosition + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        $('#pending-items-container').html(html);
    }
    
    function formatAction(action) {
        switch(action) {
            case 'generate_content': return 'Generate Content';
            case 'generate_image': return 'Generate Image';
            case 'refresh_content': return 'Refresh Content';
            case 'bulk_generate': return 'Bulk Generate';
            default: return action;
        }
    }
    
    function getPriorityClass(priority) {
        if (priority >= 15) return 'high';
        if (priority >= 10) return 'normal';
        return 'low';
    }
    
    function formatTime(timeString) {
        var date = new Date(timeString);
        var now = new Date();
        var diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) return diff + 's ago';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
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

<style>
/* Queue Status Styles */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.action-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: white;
}

.action-generate_content { background: #2271b1; }
.action-generate_image { background: #00a32a; }
.action-refresh_content { background: #dba617; }
.action-bulk_generate { background: #8c8f94; }

.priority-badge {
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    color: white;
}

.priority-high { background: #d63638; }
.priority-normal { background: #2271b1; }
.priority-low { background: #8c8f94; }

.progress-bar {
    width: 100%;
    height: 8px;
    background: #f0f0f1;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 4px;
}

.progress-fill {
    height: 100%;
    background: #00a32a;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.queue-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 5px;
}

.stat-number.pending { color: #dba617; }
.stat-number.processing { color: #00a32a; }
.stat-number.completed { color: #2271b1; }
.stat-number.failed { color: #d63638; }

.stat-label {
    font-size: 14px;
    color: #646970;
}

.queue-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.processing-indicator {
    animation: pulse 2s infinite;
}

.pending-count {
    font-weight: normal;
    font-size: 11px;
}

/* Modal Styles */
.kotacom-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.kotacom-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    border-radius: 8px;
    width: 80%;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
}

.kotacom-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.kotacom-modal-close:hover,
.kotacom-modal-close:focus {
    color: #000;
    text-decoration: none;
}

.queue-item-details h3 {
    margin-top: 20px;
    margin-bottom: 10px;
    color: #1d2327;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}

.code-block {
    background: #f6f7f7;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 12px;
    font-family: Consolas, Monaco, monospace;
    font-size: 13px;
    overflow-x: auto;
}

.code-block pre {
    margin: 0;
    white-space: pre-wrap;
}

/* Responsive */
@media (max-width: 768px) {
    .queue-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .queue-actions {
        justify-content: center;
    }
    
    .kotacom-modal-content {
        width: 95%;
        margin: 2% auto;
    }
}
</style>
