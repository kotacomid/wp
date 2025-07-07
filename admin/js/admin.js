/**
 * Kotacom AI Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Global variables
    window.kotacomAI = window.kotacomAI || {};
    
    // Initialize when document is ready
    $(document).ready(function() {
        initializeAdmin();
        initKeywordSelection();
    });
    
    /**
     * Initialize admin functionality
     */
    function initializeAdmin() {
        // Initialize tooltips
        initializeTooltips();
        
        // Initialize form validation
        initializeFormValidation();
        
        // Initialize AJAX error handling
        initializeAjaxErrorHandling();
        
        // Initialize auto-save functionality
        initializeAutoSave();
    }
    
    /**
     * Initialize tooltips
     */
    function initializeTooltips() {
        $('[data-tooltip]').each(function() {
            var $element = $(this);
            var tooltip = $element.data('tooltip');
            
            $element.attr('title', tooltip);
        });
    }
    
    /**
     * Initialize form validation
     */
    function initializeFormValidation() {
        // Validate required fields
        $('form').on('submit', function(e) {
            var $form = $(this);
            var isValid = true;
            
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('error');
                    showFieldError($field, kotacomAI.strings.required_field || 'This field is required.');
                } else {
                    $field.removeClass('error');
                    hideFieldError($field);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotice(kotacomAI.strings.validation_error || 'Please fill in all required fields.', 'error');
            }
        });
        
        // Clear errors on input
        $('[required]').on('input change', function() {
            var $field = $(this);
            if ($field.val().trim()) {
                $field.removeClass('error');
                hideFieldError($field);
            }
        });
    }
    
    /**
     * Initialize AJAX error handling
     */
    function initializeAjaxErrorHandling() {
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            console.error('AJAX Error:', thrownError);
            
            if (xhr.status === 403) {
                showNotice(kotacomAI.strings.permission_error || 'Permission denied.', 'error');
            } else if (xhr.status === 500) {
                showNotice(kotacomAI.strings.server_error || 'Server error occurred.', 'error');
            } else {
                showNotice(kotacomAI.strings.network_error || 'Network error occurred.', 'error');
            }
        });
    }
    
    /**
     * Initialize auto-save functionality
     */
    function initializeAutoSave() {
        var autoSaveTimer;
        var autoSaveDelay = 30000; // 30 seconds
        
        $('textarea, input[type="text"]').on('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                autoSaveForm();
            }, autoSaveDelay);
        });
    }
    
    /**
     * Auto-save form data
     */
    function autoSaveForm() {
        var formData = {};
        
        $('input, textarea, select').each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            var value = $field.val();
            
            if (name && value) {
                formData[name] = value;
            }
        });
        
        // Save to localStorage
        localStorage.setItem('kotacom_ai_autosave', JSON.stringify(formData));
        
        // Show auto-save indicator
        showAutoSaveIndicator();
    }
    
    /**
     * Show auto-save indicator
     */
    function showAutoSaveIndicator() {
        var $indicator = $('#autosave-indicator');
        
        if ($indicator.length === 0) {
            $indicator = $('<div id="autosave-indicator" class="autosave-indicator">Auto-saved</div>');
            $('body').append($indicator);
        }
        
        $indicator.fadeIn().delay(2000).fadeOut();
    }
    
    /**
     * Show field error
     */
    function showFieldError($field, message) {
        var $error = $field.siblings('.field-error');
        
        if ($error.length === 0) {
            $error = $('<div class="field-error"></div>');
            $field.after($error);
        }
        
        $error.text(message).show();
    }
    
    /**
     * Hide field error
     */
    function hideFieldError($field) {
        $field.siblings('.field-error').hide();
    }
    
    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        type = type || 'info';
        
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Confirm dialog
     */
    function confirmDialog(message, callback) {
        if (confirm(message)) {
            callback();
        }
    }
    
    /**
     * Loading state management
     */
    function setLoadingState($element, loading) {
        if (loading) {
            $element.prop('disabled', true);
            $element.siblings('.spinner').addClass('is-active');
        } else {
            $element.prop('disabled', false);
            $element.siblings('.spinner').removeClass('is-active');
        }
    }
    
    /**
     * Format date for display
     */
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }
    
    /**
     * Debounce function
     */
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
    /**
     * Throttle function
     */
    function throttle(func, limit) {
        var inThrottle;
        return function() {
            var args = arguments;
            var context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(function() {
                    inThrottle = false;
                }, limit);
            }
        };
    }
    
    /**
     * Copy to clipboard
     */
    function copyToClipboard(text) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
        
        showNotice('Copied to clipboard!', 'success');
    }
    
    /**
     * Keyword selection: AJAX load and tab switching (shared for generator & generator-post-template)
     */
    function initKeywordSelection() {
        // Tab switching for keyword/manual
        $(document).on('click', '.keyword-selection-tabs .tab-button', function() {
            var tab = $(this).data('tab');
            var container = $(this).closest('.postbox');
            container.find('.tab-button').removeClass('active');
            container.find('.tab-content').removeClass('active');
            $(this).addClass('active');
            if(tab === 'existing') {
                container.find('#existing-keywords').addClass('active');
                container.find('#manual-keywords').removeClass('active');
            } else {
                container.find('#manual-keywords').addClass('active');
                container.find('#existing-keywords').removeClass('active');
            }
        });

        // Tag filter change
        $(document).on('change', '#tag-filter', function() {
            loadKeywords($(this).closest('.postbox'));
        });

        // Initial load for all keyword selection blocks
        $('.keyword-selection-tabs').each(function() {
            loadKeywords($(this).closest('.postbox'));
        });
    }

    function loadKeywords(container) {
        var tagFilter = container.find('#tag-filter').val() || '';
        var keywordsList = container.find('#keywords-list');
        if (!keywordsList.length) return;
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
                    keywordsList.html(html);
                } else {
                    keywordsList.html('<div class="notice notice-error">Failed to load keywords.</div>');
                }
            },
            error: function() {
                keywordsList.html('<div class="notice notice-error">AJAX error loading keywords.</div>');
            }
        });
    }

    /**
     * Exact AJAX keyword loading logic from generator.php
     */
    function loadKeywordsFromGenerator() {
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
                } else {
                    $('#keywords-list').html('<div class="notice notice-error">Failed to load keywords.</div>');
                }
            },
            error: function() {
                $('#keywords-list').html('<div class="notice notice-error">AJAX error loading keywords.</div>');
            }
        });
    }

    // Bind to tag filter and initial load for all generator-like pages
    $(document).ready(function() {
        if ($('.keyword-selection-tabs').length) {
            $('#tag-filter').on('change', function() {
                loadKeywordsFromGenerator();
            });
            loadKeywordsFromGenerator();
        }
    });

    /**
     * Export utility functions to global scope
     */
    window.kotacomAI.utils = {
        showNotice: showNotice,
        confirmDialog: confirmDialog,
        setLoadingState: setLoadingState,
        formatDate: formatDate,
        escapeHtml: escapeHtml,
        debounce: debounce,
        throttle: throttle,
        copyToClipboard: copyToClipboard
    };
    
    // Initialize character counters
    $('textarea[maxlength], input[maxlength]').each(function() {
        var $field = $(this);
        var maxLength = parseInt($field.attr('maxlength'));
        var $counter = $('<div class="character-counter"></div>');
        
        $field.after($counter);
        
        function updateCounter() {
            var currentLength = $field.val().length;
            var remaining = maxLength - currentLength;
            
            $counter.text(remaining + ' characters remaining');
            
            if (remaining < 50) {
                $counter.addClass('warning');
            } else {
                $counter.removeClass('warning');
            }
        }
        
        $field.on('input', updateCounter);
        updateCounter();
    });
    
    // Initialize collapsible sections
    $('.collapsible-header').on('click', function() {
        var $header = $(this);
        var $content = $header.next('.collapsible-content');
        
        $content.slideToggle();
        $header.toggleClass('collapsed');
    });
    
    // Initialize sortable lists
    if ($.fn.sortable) {
        $('.sortable-list').sortable({
            handle: '.sort-handle',
            placeholder: 'sort-placeholder',
            update: function(event, ui) {
                // Handle sort update
                var order = $(this).sortable('toArray', {attribute: 'data-id'});
                console.log('New order:', order);
            }
        });
    }
    
    // Initialize drag and drop file upload
    $('.file-drop-zone').on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-over');
    });
    
    $('.file-drop-zone').on('dragleave dragend', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
    });
    
    $('.file-drop-zone').on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
        
        var files = e.originalEvent.dataTransfer.files;
        handleFileUpload(files);
    });
    
    /**
     * Handle file upload
     */
    function handleFileUpload(files) {
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            
            // Validate file type and size
            if (file.type !== 'text/plain' && file.type !== 'text/csv') {
                showNotice('Only text and CSV files are allowed.', 'error');
                continue;
            }
            
            if (file.size > 1024 * 1024) { // 1MB limit
                showNotice('File size must be less than 1MB.', 'error');
                continue;
            }
            
            // Read file content
            var reader = new FileReader();
            reader.onload = function(e) {
                var content = e.target.result;
                processFileContent(content);
            };
            reader.readAsText(file);
        }
    }
    
    /**
     * Process uploaded file content
     */
    function processFileContent(content) {
        // Process the file content based on current page
        var currentPage = window.location.href;
        
        if (currentPage.includes('kotacom-ai-keywords')) {
            // Process as keywords
            $('#bulk-keywords-input').val(content);
        } else if (currentPage.includes('kotacom-ai-prompts')) {
            // Process as prompt template
            $('#prompt-template-input').val(content);
        }
        
        showNotice('File content loaded successfully.', 'success');
    }
    
})(jQuery);
