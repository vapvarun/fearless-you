/**
 * LCCP Hour Tracker - JavaScript
 */
(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initHourTracker();
    });

    /**
     * Initialize Hour Tracker functionality
     */
    function initHourTracker() {
        // Initialize date pickers
        initDatePickers();
        
        // Initialize dynamic fields
        initDynamicFields();
        
        // Initialize form validation
        initFormValidation();
        
        // Initialize AJAX submission
        initAjaxSubmission();
        
        // Initialize filters
        initFilters();
        
        // Initialize export functionality
        initExport();
        
        // Initialize tooltips
        initTooltips();
    }

    /**
     * Initialize date pickers
     */
    function initDatePickers() {
        // Session date picker
        $('#session_date').attr('max', getCurrentDate());
        
        // Set default to today
        if (!$('#session_date').val()) {
            $('#session_date').val(getCurrentDate());
        }
        
        // Date range filters
        $('.date-filter').on('change', function() {
            filterHours();
        });
    }

    /**
     * Initialize dynamic form fields
     */
    function initDynamicFields() {
        // Session type change handler
        $('#session_type').on('change', function() {
            const type = $(this).val();
            toggleFieldsByType(type);
        });
        
        // Client type change handler
        $('#client_type').on('change', function() {
            const type = $(this).val();
            toggleClientFields(type);
        });
        
        // Duration calculator
        $('.duration-input').on('input', function() {
            calculateDuration();
        });
        
        // Auto-save draft
        let autoSaveTimer;
        $('.lccp-hour-form input, .lccp-hour-form select, .lccp-hour-form textarea').on('input change', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                saveDraft();
            }, 2000);
        });
    }

    /**
     * Toggle fields based on session type
     */
    function toggleFieldsByType(type) {
        // Hide all conditional fields first
        $('.field-group[data-condition]').hide();
        
        // Show fields for selected type
        $('.field-group[data-condition*="' + type + '"]').fadeIn();
        
        // Update required fields
        updateRequiredFields(type);
    }

    /**
     * Toggle client fields based on type
     */
    function toggleClientFields(type) {
        if (type === 'group') {
            $('.field-group.group-details').fadeIn();
            $('.field-group.individual-details').hide();
        } else {
            $('.field-group.individual-details').fadeIn();
            $('.field-group.group-details').hide();
        }
    }

    /**
     * Calculate session duration
     */
    function calculateDuration() {
        const hours = parseInt($('#hours').val()) || 0;
        const minutes = parseInt($('#minutes').val()) || 0;
        
        // Update total display
        const total = hours + (minutes / 60);
        $('.duration-display').text(total.toFixed(2) + ' hours');
        
        // Update hidden field
        $('#total_duration').val(total);
        
        // Update progress if applicable
        updateProgress();
    }

    /**
     * Update progress indicators
     */
    function updateProgress() {
        const totalHours = parseFloat($('.progress-bar').data('total')) || 0;
        const requiredHours = parseFloat($('.progress-bar').data('required')) || 75;
        
        if (requiredHours > 0) {
            const percentage = Math.min((totalHours / requiredHours) * 100, 100);
            
            $('.progress-fill').css('width', percentage + '%');
            $('.progress-text').text(percentage.toFixed(1) + '%');
            
            // Update milestone indicators
            updateMilestones(totalHours);
        }
    }

    /**
     * Update milestone achievements
     */
    function updateMilestones(totalHours) {
        const milestones = [25, 50, 75];
        
        milestones.forEach(function(milestone) {
            if (totalHours >= milestone) {
                $('.milestone-' + milestone).addClass('achieved');
            }
        });
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Basic validation without jQuery Validate plugin
        $('.lccp-hour-form').on('submit', function(e) {
            let isValid = true;
            const errors = [];
            
            // Check required fields
            $(this).find('[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    const label = $(this).closest('.field-group').find('label').text();
                    errors.push(label + ' is required');
                    $(this).addClass('error');
                }
            });
            
            // Validate duration
            const hours = parseInt($('#hours').val()) || 0;
            const minutes = parseInt($('#minutes').val()) || 0;
            
            if (hours === 0 && minutes === 0) {
                isValid = false;
                errors.push('Please enter a valid duration');
            }
            
            if (minutes >= 60) {
                isValid = false;
                errors.push('Minutes must be less than 60');
            }
            
            // Validate date
            const sessionDate = new Date($('#session_date').val());
            const today = new Date();
            
            if (sessionDate > today) {
                isValid = false;
                errors.push('Session date cannot be in the future');
            }
            
            if (!isValid) {
                e.preventDefault();
                showNotification(errors.join('<br>'), 'error');
                return false;
            }
        });
        
        // Remove error class on input
        $('.lccp-hour-form input, .lccp-hour-form select, .lccp-hour-form textarea').on('input change', function() {
            $(this).removeClass('error');
        });
    }

    /**
     * Initialize AJAX form submission
     */
    function initAjaxSubmission() {
        $('.lccp-hour-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('.btn-submit');
            const formData = new FormData(this);
            
            // Add action for WordPress AJAX
            formData.append('action', 'lccp_log_hours');
            formData.append('nonce', lccp_ajax.nonce);
            
            // Disable submit button
            $submitBtn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: lccp_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showNotification('Hours logged successfully!', 'success');
                        
                        // Reset form
                        $form[0].reset();
                        
                        // Refresh hours list
                        refreshHoursList();
                        
                        // Update progress
                        updateProgress();
                        
                        // Clear draft
                        clearDraft();
                    } else {
                        showNotification(response.data.message || 'Error saving hours', 'error');
                    }
                },
                error: function() {
                    showNotification('Connection error. Please try again.', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Log Hours');
                }
            });
        });
    }

    /**
     * Refresh hours list via AJAX
     */
    function refreshHoursList() {
        const $container = $('.hours-list-container');
        
        if ($container.length === 0) return;
        
        $.ajax({
            url: lccp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lccp_get_hours_list',
                nonce: lccp_ajax.nonce,
                filters: getActiveFilters()
            },
            success: function(response) {
                if (response.success) {
                    $container.html(response.data.html);
                    initHoursListActions();
                }
            }
        });
    }

    /**
     * Initialize hours list actions
     */
    function initHoursListActions() {
        // Edit button
        $('.btn-edit-hour').on('click', function() {
            const hourId = $(this).data('id');
            loadHourForEdit(hourId);
        });
        
        // Delete button
        $('.btn-delete-hour').on('click', function() {
            const hourId = $(this).data('id');
            if (confirm('Are you sure you want to delete this entry?')) {
                deleteHour(hourId);
            }
        });
        
        // View details
        $('.btn-view-details').on('click', function() {
            const hourId = $(this).data('id');
            showHourDetails(hourId);
        });
    }

    /**
     * Load hour entry for editing
     */
    function loadHourForEdit(hourId) {
        $.ajax({
            url: lccp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lccp_get_hour_entry',
                nonce: lccp_ajax.nonce,
                hour_id: hourId
            },
            success: function(response) {
                if (response.success) {
                    populateForm(response.data);
                    scrollToForm();
                }
            }
        });
    }

    /**
     * Delete hour entry
     */
    function deleteHour(hourId) {
        $.ajax({
            url: lccp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lccp_delete_hour',
                nonce: lccp_ajax.nonce,
                hour_id: hourId
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Entry deleted successfully', 'success');
                    refreshHoursList();
                    updateProgress();
                } else {
                    showNotification('Error deleting entry', 'error');
                }
            }
        });
    }

    /**
     * Initialize filter functionality
     */
    function initFilters() {
        // Quick filters
        $('.filter-quick button').on('click', function() {
            $('.filter-quick button').removeClass('active');
            $(this).addClass('active');
            
            const filter = $(this).data('filter');
            applyQuickFilter(filter);
        });
        
        // Advanced filters
        $('.filter-select, .filter-date').on('change', function() {
            filterHours();
        });
        
        // Search
        let searchTimer;
        $('.filter-search').on('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                filterHours();
            }, 500);
        });
        
        // Clear filters
        $('.btn-clear-filters').on('click', function() {
            clearFilters();
        });
    }

    /**
     * Apply quick filter
     */
    function applyQuickFilter(filter) {
        const today = new Date();
        let startDate, endDate;
        
        switch(filter) {
            case 'week':
                startDate = new Date(today.setDate(today.getDate() - 7));
                endDate = new Date();
                break;
            case 'month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                endDate = new Date();
                break;
            case 'all':
            default:
                startDate = '';
                endDate = '';
        }
        
        $('#filter_start_date').val(formatDate(startDate));
        $('#filter_end_date').val(formatDate(endDate));
        
        filterHours();
    }

    /**
     * Filter hours list
     */
    function filterHours() {
        refreshHoursList();
    }

    /**
     * Get active filters
     */
    function getActiveFilters() {
        return {
            start_date: $('#filter_start_date').val(),
            end_date: $('#filter_end_date').val(),
            session_type: $('#filter_session_type').val(),
            search: $('.filter-search').val()
        };
    }

    /**
     * Clear all filters
     */
    function clearFilters() {
        $('.filter-select').val('');
        $('.filter-date').val('');
        $('.filter-search').val('');
        $('.filter-quick button').removeClass('active');
        $('.filter-quick button[data-filter="all"]').addClass('active');
        
        filterHours();
    }

    /**
     * Initialize export functionality
     */
    function initExport() {
        $('.btn-export').on('click', function() {
            const format = $(this).data('format');
            exportHours(format);
        });
    }

    /**
     * Export hours data
     */
    function exportHours(format) {
        const filters = getActiveFilters();
        
        // Create form for download
        const $form = $('<form>', {
            action: lccp_ajax.ajax_url,
            method: 'POST'
        });
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'lccp_export_hours'
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: lccp_ajax.nonce
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'format',
            value: format
        }));
        
        // Add filters
        Object.keys(filters).forEach(function(key) {
            $form.append($('<input>', {
                type: 'hidden',
                name: 'filters[' + key + ']',
                value: filters[key]
            }));
        });
        
        $form.appendTo('body').submit().remove();
    }

    /**
     * Save form as draft
     */
    function saveDraft() {
        const formData = $('.lccp-hour-form').serialize();
        localStorage.setItem('lccp_hour_draft', formData);
        showNotification('Draft saved', 'info', 2000);
    }

    /**
     * Load draft if exists
     */
    function loadDraft() {
        const draft = localStorage.getItem('lccp_hour_draft');
        if (draft) {
            // Parse and populate form
            const params = new URLSearchParams(draft);
            for(const [key, value] of params) {
                $('[name="' + key + '"]').val(value);
            }
            showNotification('Draft loaded', 'info');
        }
    }

    /**
     * Clear draft
     */
    function clearDraft() {
        localStorage.removeItem('lccp_hour_draft');
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('.has-tooltip').hover(
            function() {
                const tooltip = $(this).data('tooltip');
                const $tooltip = $('<div class="tooltip-popup">' + tooltip + '</div>');
                $('body').append($tooltip);
                
                const offset = $(this).offset();
                $tooltip.css({
                    top: offset.top - $tooltip.outerHeight() - 10,
                    left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                }).fadeIn(200);
            },
            function() {
                $('.tooltip-popup').fadeOut(200, function() {
                    $(this).remove();
                });
            }
        );
    }

    /**
     * Show notification
     */
    function showNotification(message, type, duration) {
        duration = duration || 5000;
        
        const $notification = $('<div class="lccp-notification ' + type + '">' +
            '<span class="notification-message">' + message + '</span>' +
            '<button class="notification-close">&times;</button>' +
            '</div>');
        
        $('body').append($notification);
        
        $notification.animate({ right: '20px' }, 300);
        
        // Auto-hide
        setTimeout(function() {
            hideNotification($notification);
        }, duration);
        
        // Close button
        $notification.find('.notification-close').on('click', function() {
            hideNotification($notification);
        });
    }

    /**
     * Hide notification
     */
    function hideNotification($notification) {
        $notification.animate({ right: '-400px' }, 300, function() {
            $(this).remove();
        });
    }

    /**
     * Populate form with data
     */
    function populateForm(data) {
        Object.keys(data).forEach(function(key) {
            const $field = $('[name="' + key + '"]');
            if ($field.length) {
                $field.val(data[key]).trigger('change');
            }
        });
    }

    /**
     * Scroll to form
     */
    function scrollToForm() {
        $('html, body').animate({
            scrollTop: $('.lccp-hour-form').offset().top - 100
        }, 500);
    }

    /**
     * Format date for display
     */
    function formatDate(date) {
        if (!date) return '';
        if (typeof date === 'string') return date;
        
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        return year + '-' + month + '-' + day;
    }

    /**
     * Get current date in YYYY-MM-DD format
     */
    function getCurrentDate() {
        return formatDate(new Date());
    }

    /**
     * Update required fields based on session type
     */
    function updateRequiredFields(type) {
        // Remove all conditional required
        $('.conditional-required').prop('required', false);
        
        // Add required based on type
        switch(type) {
            case 'individual':
                $('#client_name, #client_email').prop('required', true);
                break;
            case 'group':
                $('#group_size, #group_description').prop('required', true);
                break;
            case 'practice':
                $('#practice_partner').prop('required', true);
                break;
        }
    }

    /**
     * Show hour details in modal
     */
    function showHourDetails(hourId) {
        $.ajax({
            url: lccp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lccp_get_hour_details',
                nonce: lccp_ajax.nonce,
                hour_id: hourId
            },
            success: function(response) {
                if (response.success) {
                    showModal(response.data.title, response.data.content);
                }
            }
        });
    }

    /**
     * Show modal
     */
    function showModal(title, content) {
        const $modal = $('<div class="lccp-modal">' +
            '<div class="modal-overlay"></div>' +
            '<div class="modal-content">' +
                '<div class="modal-header">' +
                    '<h3>' + title + '</h3>' +
                    '<button class="modal-close">&times;</button>' +
                '</div>' +
                '<div class="modal-body">' + content + '</div>' +
            '</div>' +
        '</div>');
        
        $('body').append($modal);
        
        // Close handlers
        $modal.find('.modal-close, .modal-overlay').on('click', function() {
            $modal.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Fade in
        $modal.fadeIn(300);
    }

    // Load draft on page load
    $(window).on('load', function() {
        if ($('.lccp-hour-form').length && !$('.lccp-hour-form input[name="hour_id"]').val()) {
            loadDraft();
        }
    });

})(jQuery);