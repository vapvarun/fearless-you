/**
 * LCCP Checklist JavaScript
 * Handles checklist interactions and progress tracking
 */

(function($) {
    'use strict';

    var LCCPChecklist = {
        
        init: function() {
            this.bindEvents();
            this.updateAllProgress();
            this.loadLocalProgress();
        },
        
        bindEvents: function() {
            // Checkbox change event
            $(document).on('change', '.lccp-checklist-checkbox', this.handleCheckboxChange.bind(this));
            
            // Initialize tooltips if needed
            this.initTooltips();
        },
        
        handleCheckboxChange: function(e) {
            var $checkbox = $(e.target);
            var $item = $checkbox.closest('.lccp-checklist-item');
            var $checklist = $checkbox.closest('.lccp-checklist');
            
            // Toggle checked class
            if ($checkbox.is(':checked')) {
                $item.addClass('lccp-checked just-checked');
                setTimeout(function() {
                    $item.removeClass('just-checked');
                }, 300);
            } else {
                $item.removeClass('lccp-checked');
            }
            
            // Update progress
            this.updateProgress($checklist);
            
            // Save progress
            if ($checklist.data('save-progress') === 'yes') {
                this.saveProgress($checkbox, $checklist);
            } else {
                this.saveLocalProgress($checkbox, $checklist);
            }
        },
        
        updateProgress: function($checklist) {
            var total = $checklist.find('.lccp-checklist-checkbox').length;
            var checked = $checklist.find('.lccp-checklist-checkbox:checked').length;
            var percentage = total > 0 ? Math.round((checked / total) * 100) : 0;
            
            // Update progress bar
            var $progressBar = $checklist.find('.progress-fill');
            var $progressText = $checklist.find('.progress-text');
            
            $progressBar.css('width', percentage + '%');
            
            if (percentage === 100) {
                $progressText.text(lccp_checklist.strings.complete);
                $checklist.addClass('lccp-complete');
                
                // Trigger completion event
                $(document).trigger('lccp_checklist_complete', [$checklist]);
            } else {
                $progressText.text(lccp_checklist.strings.progress.replace('%d', percentage));
                $checklist.removeClass('lccp-complete');
            }
            
            // Add animation class
            if (percentage > 0) {
                $progressBar.addClass('animated');
            }
        },
        
        updateAllProgress: function() {
            $('.lccp-checklist').each(function() {
                LCCPChecklist.updateProgress($(this));
            });
        },
        
        saveProgress: function($checkbox, $checklist) {
            if (!lccp_checklist.is_logged_in) {
                this.saveLocalProgress($checkbox, $checklist);
                return;
            }
            
            var checklistId = $checklist.data('checklist-id');
            var postId = $checklist.data('post-id');
            var itemIndex = $checkbox.data('index');
            var isChecked = $checkbox.is(':checked');
            var totalItems = $checklist.find('.lccp-checklist-checkbox').length;
            
            $.ajax({
                url: lccp_checklist.ajax_url,
                type: 'POST',
                data: {
                    action: 'lccp_update_checklist',
                    nonce: $('#lccp_checklist_nonce').val() || lccp_checklist.nonce,
                    post_id: postId,
                    checklist_id: checklistId,
                    item_index: itemIndex,
                    checked: isChecked,
                    total_items: totalItems
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Progress saved:', response.data.progress + '%');
                        
                        // Trigger custom event
                        $(document).trigger('lccp_progress_saved', [response.data]);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to save progress:', error);
                    // Fallback to local storage
                    LCCPChecklist.saveLocalProgress($checkbox, $checklist);
                }
            });
        },
        
        saveLocalProgress: function($checkbox, $checklist) {
            if (!window.localStorage) {
                return;
            }
            
            var checklistId = $checklist.data('checklist-id');
            var postId = $checklist.data('post-id');
            var storageKey = 'lccp_checklist_' + postId + '_' + checklistId;
            
            var progress = {};
            
            $checklist.find('.lccp-checklist-checkbox').each(function() {
                var index = $(this).data('index');
                progress[index] = $(this).is(':checked');
            });
            
            localStorage.setItem(storageKey, JSON.stringify(progress));
        },
        
        loadLocalProgress: function() {
            if (!window.localStorage) {
                return;
            }
            
            $('.lccp-checklist').each(function() {
                var $checklist = $(this);
                
                // Skip if user is logged in and save_progress is enabled
                if (lccp_checklist.is_logged_in && $checklist.data('save-progress') === 'yes') {
                    return;
                }
                
                var checklistId = $checklist.data('checklist-id');
                var postId = $checklist.data('post-id');
                var storageKey = 'lccp_checklist_' + postId + '_' + checklistId;
                
                var savedProgress = localStorage.getItem(storageKey);
                
                if (savedProgress) {
                    try {
                        var progress = JSON.parse(savedProgress);
                        
                        $.each(progress, function(index, checked) {
                            var $checkbox = $checklist.find('.lccp-checklist-checkbox[data-index="' + index + '"]');
                            if ($checkbox.length) {
                                $checkbox.prop('checked', checked);
                                if (checked) {
                                    $checkbox.closest('.lccp-checklist-item').addClass('lccp-checked');
                                }
                            }
                        });
                        
                        // Update progress display
                        LCCPChecklist.updateProgress($checklist);
                        
                    } catch (e) {
                        console.error('Failed to parse saved progress:', e);
                    }
                }
            });
        },
        
        initTooltips: function() {
            // Add tooltips for better UX
            $('.lccp-checklist-item').attr('title', 'Click to mark as complete');
        },
        
        // Public method to get checklist stats
        getStats: function(checklistId) {
            var $checklist = checklistId ? 
                $('.lccp-checklist[data-checklist-id="' + checklistId + '"]') : 
                $('.lccp-checklist');
            
            var stats = {
                total: 0,
                checked: 0,
                percentage: 0,
                checklists: []
            };
            
            $checklist.each(function() {
                var $this = $(this);
                var total = $this.find('.lccp-checklist-checkbox').length;
                var checked = $this.find('.lccp-checklist-checkbox:checked').length;
                
                stats.total += total;
                stats.checked += checked;
                
                stats.checklists.push({
                    id: $this.data('checklist-id'),
                    total: total,
                    checked: checked,
                    percentage: total > 0 ? Math.round((checked / total) * 100) : 0
                });
            });
            
            stats.percentage = stats.total > 0 ? Math.round((stats.checked / stats.total) * 100) : 0;
            
            return stats;
        },
        
        // Public method to reset checklist
        reset: function(checklistId) {
            var $checklist = $('.lccp-checklist[data-checklist-id="' + checklistId + '"]');
            
            $checklist.find('.lccp-checklist-checkbox').prop('checked', false);
            $checklist.find('.lccp-checklist-item').removeClass('lccp-checked');
            
            this.updateProgress($checklist);
            
            // Clear local storage
            if (window.localStorage) {
                var postId = $checklist.data('post-id');
                var storageKey = 'lccp_checklist_' + postId + '_' + checklistId;
                localStorage.removeItem(storageKey);
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        LCCPChecklist.init();
    });
    
    // Expose to global scope for external use
    window.LCCPChecklist = LCCPChecklist;

})(jQuery);