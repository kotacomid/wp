<?php
/**
 * Keywords Management admin page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Keywords Management', 'kotacom-ai'); ?></h1>
    
    <div class="kotacom-ai-keywords">
        <!-- Add Keywords Section -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Add Keywords', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <div class="add-keywords-tabs">
                    <button type="button" class="tab-button active" data-tab="single"><?php _e('Add Single', 'kotacom-ai'); ?></button>
                    <button type="button" class="tab-button" data-tab="bulk"><?php _e('Add Bulk', 'kotacom-ai'); ?></button>
                </div>
                
                <!-- Single Keyword Form -->
                <div id="single-keyword" class="tab-content active">
                    <form id="add-single-keyword-form">
                        <?php wp_nonce_field('kotacom_ai_nonce', 'kotacom_ai_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Keyword', 'kotacom-ai'); ?></th>
                                <td>
                                    <input type="text" name="keyword" id="single-keyword-input" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Tags', 'kotacom-ai'); ?></th>
                                <td>
                                    <input type="text" name="tags" id="single-tags-input" class="regular-text" placeholder="<?php _e('Comma-separated tags', 'kotacom-ai'); ?>">
                                    <p class="description"><?php _e('Enter tags separated by commas. Existing tags will be suggested.', 'kotacom-ai'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Add Keyword', 'kotacom-ai'); ?></button>
                            <span class="spinner"></span>
                        </p>
                    </form>
                </div>
                
                <!-- Bulk Keywords Form -->
                <div id="bulk-keywords" class="tab-content">
                    <form id="add-bulk-keywords-form">
                        <?php wp_nonce_field('kotacom_ai_nonce', 'kotacom_ai_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Keywords', 'kotacom-ai'); ?></th>
                                <td>
                                    <textarea name="keywords" id="bulk-keywords-input" rows="10" class="large-text" placeholder="<?php _e('Enter keywords, one per line', 'kotacom-ai'); ?>" required></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Tags', 'kotacom-ai'); ?></th>
                                <td>
                                    <input type="text" name="tags" id="bulk-tags-input" class="regular-text" placeholder="<?php _e('Comma-separated tags for all keywords', 'kotacom-ai'); ?>">
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Add Keywords', 'kotacom-ai'); ?></button>
                            <span class="spinner"></span>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Keywords List Section -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Keywords List', 'kotacom-ai'); ?></h2>
            <div class="inside">
                <!-- Filters -->
                <div class="keywords-filters">
                    <input type="text" id="keyword-search" placeholder="<?php _e('Search keywords...', 'kotacom-ai'); ?>" class="regular-text">
                    <select id="tag-filter">
                        <option value=""><?php _e('All Tags', 'kotacom-ai'); ?></option>
                        <?php foreach ($tags as $tag): ?>
                            <option value="<?php echo esc_attr($tag); ?>"><?php echo esc_html($tag); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="filter-keywords" class="button"><?php _e('Filter', 'kotacom-ai'); ?></button>
                    <button type="button" id="clear-filters" class="button"><?php _e('Clear', 'kotacom-ai'); ?></button>
                </div>
                
                <!-- Bulk Actions -->
                <div class="bulk-actions">
                    <select id="bulk-action">
                        <option value=""><?php _e('Bulk Actions', 'kotacom-ai'); ?></option>
                        <option value="edit-tags"><?php _e('Edit Tags', 'kotacom-ai'); ?></option>
                        <option value="delete"><?php _e('Delete', 'kotacom-ai'); ?></option>
                    </select>
                    <button type="button" id="apply-bulk-action" class="button"><?php _e('Apply', 'kotacom-ai'); ?></button>
                </div>
                
                <!-- Keywords Table -->
                <div id="keywords-table-container">
                    <table class="wp-list-table widefat fixed striped" id="keywords-table">
                        <thead>
                            <tr>
                                <td class="check-column">
                                    <input type="checkbox" id="select-all-keywords">
                                </td>
                                <th><?php _e('Keyword', 'kotacom-ai'); ?></th>
                                <th><?php _e('Tags', 'kotacom-ai'); ?></th>
                                <th><?php _e('Created', 'kotacom-ai'); ?></th>
                                <th><?php _e('Actions', 'kotacom-ai'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="keywords-table-body">
                            <!-- Keywords will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="tablenav bottom">
                    <div class="tablenav-pages" id="keywords-pagination">
                        <!-- Pagination will be loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Keyword Modal -->
<div id="edit-keyword-modal" class="kotacom-modal" style="display: none;">
    <div class="kotacom-modal-content">
        <span class="kotacom-modal-close">&times;</span>
        <h2><?php _e('Edit Keyword', 'kotacom-ai'); ?></h2>
        <form id="edit-keyword-form">
            <input type="hidden" name="keyword_id" id="edit-keyword-id">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Keyword', 'kotacom-ai'); ?></th>
                    <td>
                        <input type="text" name="keyword" id="edit-keyword-input" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Tags', 'kotacom-ai'); ?></th>
                    <td>
                        <input type="text" name="tags" id="edit-tags-input" class="regular-text">
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Update Keyword', 'kotacom-ai'); ?></button>
                <button type="button" class="button" onclick="closeModal()"><?php _e('Cancel', 'kotacom-ai'); ?></button>
                <span class="spinner"></span>
            </p>
        </form>
    </div>
</div>

<!-- Bulk Edit Tags Modal -->
<div id="bulk-edit-tags-modal" class="kotacom-modal" style="display: none;">
    <div class="kotacom-modal-content">
        <span class="kotacom-modal-close">&times;</span>
        <h2><?php _e('Bulk Edit Tags', 'kotacom-ai'); ?></h2>
        <form id="bulk-edit-tags-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Action', 'kotacom-ai'); ?></th>
                    <td>
                        <select name="tag_action" id="tag-action">
                            <option value="replace"><?php _e('Replace Tags', 'kotacom-ai'); ?></option>
                            <option value="add"><?php _e('Add Tags', 'kotacom-ai'); ?></option>
                            <option value="remove"><?php _e('Remove Tags', 'kotacom-ai'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Tags', 'kotacom-ai'); ?></th>
                    <td>
                        <input type="text" name="tags" id="bulk-edit-tags-input" class="regular-text" placeholder="<?php _e('Comma-separated tags', 'kotacom-ai'); ?>">
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Update Tags', 'kotacom-ai'); ?></button>
                <button type="button" class="button" onclick="closeModal()"><?php _e('Cancel', 'kotacom-ai'); ?></button>
                <span class="spinner"></span>
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var currentPage = 1;
    var selectedKeywords = [];
    
    // Tab switching
    $('.tab-button').on('click', function() {
        var tab = $(this).data('tab');
        
        $('.tab-button').removeClass('active');
        $('.tab-content').removeClass('active');
        
        $(this).addClass('active');
        $('#' + tab + '-keyword, #' + tab + '-keywords').addClass('active');
    });
    
    // Tags autocomplete
    $('#single-tags-input, #bulk-tags-input, #edit-tags-input, #bulk-edit-tags-input').autocomplete({
        source: <?php echo json_encode($tags); ?>,
        minLength: 0
    });
    
    // Add single keyword
    $('#add-single-keyword-form').on('submit', function(e) {
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
                action: 'kotacom_add_keyword',
                nonce: kotacomAI.nonce,
                keyword: $('#single-keyword-input').val(),
                tags: $('#single-tags-input').val()
            },
            success: function(response) {
                if (response.success) {
                    $form[0].reset();
                    loadKeywords();
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
    
    // Add bulk keywords
    $('#add-bulk-keywords-form').on('submit', function(e) {
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
                action: 'kotacom_add_keywords_bulk',
                nonce: kotacomAI.nonce,
                keywords: $('#bulk-keywords-input').val(),
                tags: $('#bulk-tags-input').val()
            },
            success: function(response) {
                if (response.success) {
                    $form[0].reset();
                    loadKeywords();
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
    
    // Filter keywords
    $('#filter-keywords').on('click', function() {
        currentPage = 1;
        loadKeywords();
    });
    
    // Clear filters
    $('#clear-filters').on('click', function() {
        $('#keyword-search').val('');
        $('#tag-filter').val('');
        currentPage = 1;
        loadKeywords();
    });
    
    // Search on enter
    $('#keyword-search').on('keypress', function(e) {
        if (e.which === 13) {
            currentPage = 1;
            loadKeywords();
        }
    });
    
    // Select all keywords
    $('#select-all-keywords').on('change', function() {
        var checked = $(this).is(':checked');
        $('.keyword-checkbox').prop('checked', checked);
        updateSelectedKeywords();
    });
    
    // Apply bulk action
    $('#apply-bulk-action').on('click', function() {
        var action = $('#bulk-action').val();
        var selected = $('.keyword-checkbox:checked');
        
        if (!action) {
            alert('<?php _e('Please select an action.', 'kotacom-ai'); ?>');
            return;
        }
        
        if (selected.length === 0) {
            alert('<?php _e('Please select keywords to perform bulk action.', 'kotacom-ai'); ?>');
            return;
        }
        
        if (action === 'edit-tags') {
            selectedKeywords = [];
            selected.each(function() {
                selectedKeywords.push($(this).val());
            });
            $('#bulk-edit-tags-modal').show();
        } else if (action === 'delete') {
            if (confirm('<?php _e('Are you sure you want to delete selected keywords?', 'kotacom-ai'); ?>')) {
                deleteSelectedKeywords();
            }
        }
    });
    
    // Load initial keywords
    loadKeywords();
    
    function loadKeywords() {
        $.ajax({
            url: kotacomAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_get_keywords',
                nonce: kotacomAI.nonce,
                page: currentPage,
                per_page: 20,
                search: $('#keyword-search').val(),
                tag_filter: $('#tag-filter').val()
            },
            success: function(response) {
                if (response.success) {
                    displayKeywords(response.data.keywords);
                    displayPagination(response.data.pages, response.data.total);
                }
            }
        });
    }
    
    function displayKeywords(keywords) {
        var html = '';
        
        $.each(keywords, function(index, keyword) {
            html += '<tr>';
            html += '<th scope="row" class="check-column">';
            html += '<input type="checkbox" class="keyword-checkbox" value="' + keyword.id + '">';
            html += '</th>';
            html += '<td><strong>' + keyword.keyword + '</strong></td>';
            html += '<td>' + (keyword.tags || '') + '</td>';
            html += '<td>' + keyword.created_at + '</td>';
            html += '<td>';
            html += '<button type="button" class="button button-small edit-keyword" data-id="' + keyword.id + '" data-keyword="' + keyword.keyword + '" data-tags="' + (keyword.tags || '') + '"><?php _e('Edit', 'kotacom-ai'); ?></button> ';
            html += '<button type="button" class="button button-small delete-keyword" data-id="' + keyword.id + '"><?php _e('Delete', 'kotacom-ai'); ?></button>';
            html += '</td>';
            html += '</tr>';
        });
        
        $('#keywords-table-body').html(html);
        
        // Bind events
        $('.keyword-checkbox').on('change', updateSelectedKeywords);
        $('.edit-keyword').on('click', openEditModal);
        $('.delete-keyword').on('click', deleteKeyword);
    }
    
    function displayPagination(totalPages, totalItems) {
        var html = '';
        
        if (totalPages > 1) {
            html += '<span class="displaying-num">' + totalItems + ' items</span>';
            html += '<span class="pagination-links">';
            
            if (currentPage > 1) {
                html += '<a class="page-link" data-page="1">&laquo;</a>';
                html += '<a class="page-link" data-page="' + (currentPage - 1) + '">&lsaquo;</a>';
            }
            
            for (var i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
                if (i === currentPage) {
                    html += '<span class="current">' + i + '</span>';
                } else {
                    html += '<a class="page-link" data-page="' + i + '">' + i + '</a>';
                }
            }
            
            if (currentPage < totalPages) {
                html += '<a class="page-link" data-page="' + (currentPage + 1) + '">&rsaquo;</a>';
                html += '<a class="page-link" data-page="' + totalPages + '">&raquo;</a>';
            }
            
            html += '</span>';
        }
        
        $('#keywords-pagination').html(html);
        
        // Bind pagination events
        $('.page-link').on('click', function(e) {
            e.preventDefault();
            currentPage = parseInt($(this).data('page'));
            loadKeywords();
        });
    }
    
    function updateSelectedKeywords() {
        selectedKeywords = [];
        $('.keyword-checkbox:checked').each(function() {
            selectedKeywords.push($(this).val());
        });
    }
    
    function openEditModal() {
        var id = $(this).data('id');
        var keyword = $(this).data('keyword');
        var tags = $(this).data('tags');
        
        $('#edit-keyword-id').val(id);
        $('#edit-keyword-input').val(keyword);
        $('#edit-tags-input').val(tags);
        
        $('#edit-keyword-modal').show();
    }
    
    function deleteKeyword() {
        var id = $(this).data('id');
        
        if (confirm(kotacomAI.strings.confirm_delete)) {
            $.ajax({
                url: kotacomAI.ajaxurl,
                type: 'POST',
                data: {
                    action: 'kotacom_delete_keyword',
                    nonce: kotacomAI.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        loadKeywords();
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
    
    // Edit keyword form
    $('#edit-keyword-form').on('submit', function(e) {
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
                action: 'kotacom_update_keyword',
                nonce: kotacomAI.nonce,
                id: $('#edit-keyword-id').val(),
                keyword: $('#edit-keyword-input').val(),
                tags: $('#edit-tags-input').val()
            },
            success: function(response) {
                if (response.success) {
                    closeModal();
                    loadKeywords();
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
});
</script>
