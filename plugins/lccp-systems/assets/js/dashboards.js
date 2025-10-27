/**
 * LCCP Dashboard JavaScript
 */

(function($) {
    'use strict';

    var LCCPDashboard = {
        
        init: function() {
            this.bindEvents();
            this.initProgressBars();
            this.initTooltips();
        },
        
        bindEvents: function() {
            // Toggle hour log visibility
            $(document).on('click', '.lccp-toggle-hour-log', this.toggleHourLog);
            
            // View student details
            $(document).on('click', '.lccp-view-details', this.viewStudentDetails);
            
            // Send message to student
            $(document).on('click', '.lccp-send-message', this.sendMessage);
            
            // Assign/unassign students
            $(document).on('click', '.lccp-assign-student', this.assignStudent);
            $(document).on('click', '.lccp-unassign-student', this.unassignStudent);
            
            // Close modal
            $(document).on('click', '.lccp-close-modal', this.closeModal);
            $(document).on('click', '.modal-overlay', this.closeModal);
            
            // Hour form submission
            $(document).on('submit', '#lccp-hour-tracker-form', this.submitHourForm);
            
            // Delete hour entry
            $(document).on('click', '.lccp-delete-entry', this.deleteEntry);
        },
        
        initProgressBars: function() {
            $('.lccp-progress-bar').each(function() {
                var $bar = $(this);
                var $fill = $bar.find('.lccp-progress-fill');
                var progress = $fill.data('progress');
                
                if (progress) {
                    setTimeout(function() {
                        $fill.css('width', progress + '%');
                    }, 100);
                }
            });
        },
        
        initTooltips: function() {
            $('.lccp-tooltip').hover(
                function() {
                    var text = $(this).data('tooltip');
                    $('<div class="lccp-tooltip-popup">' + text + '</div>')
                        .appendTo('body')
                        .fadeIn('fast');
                },
                function() {
                    $('.lccp-tooltip-popup').remove();
                }
            );
        },
        
        toggleHourLog: function(e) {
            e.preventDefault();
            var $container = $('.lccp-hour-log-container');
            var $button = $(this);
            
            $container.slideToggle(function() {
                if ($container.is(':visible')) {
                    $button.text('Hide Hour Log');
                } else {
                    $button.text('View Hour Log');
                }
            });
        },
        
        viewStudentDetails: function(e) {
            e.preventDefault();
            var studentId = $(this).data('student-id');
            
            $.ajax({
                url: lccp_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'lccp_get_student_details',
                    student_id: studentId,
                    nonce: lccp_dashboard.nonce
                },
                beforeSend: function() {
                    LCCPDashboard.showLoading();
                },
                success: function(response) {
                    if (response.success) {
                        LCCPDashboard.showModal(response.data.html);
                    } else {
                        alert('Error loading student details');
                    }
                },
                complete: function() {
                    LCCPDashboard.hideLoading();
                }
            });
        },
        
        sendMessage: function(e) {
            e.preventDefault();
            var studentId = $(this).data('student-id');
            
            var message = prompt('Enter your message:');
            if (!message) return;
            
            $.ajax({
                url: lccp_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'lccp_send_message',
                    student_id: studentId,
                    message: message,
                    nonce: lccp_dashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Message sent successfully!');
                    } else {
                        alert('Error sending message');
                    }
                }
            });
        },
        
        assignStudent: function(e) {
            e.preventDefault();
            var $button = $(this);
            var studentId = $button.data('student-id');
            
            $.ajax({
                url: lccp_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'lccp_assign_student',
                    student_id: studentId,
                    nonce: lccp_dashboard.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text('Assigning...');
                },
                success: function(response) {
                    if (response.success) {
                        $button
                            .removeClass('lccp-assign-student')
                            .addClass('lccp-unassign-student')
                            .text('Unassign')
                            .prop('disabled', false);
                        
                        // Refresh the dashboard
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error assigning student');
                        $button.prop('disabled', false).text('Assign');
                    }
                }
            });
        },
        
        unassignStudent: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to unassign this student?')) {
                return;
            }
            
            var $button = $(this);
            var studentId = $button.data('student-id');
            
            $.ajax({
                url: lccp_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'lccp_unassign_student',
                    student_id: studentId,
                    nonce: lccp_dashboard.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text('Unassigning...');
                },
                success: function(response) {
                    if (response.success) {
                        // Refresh the dashboard
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error unassigning student');
                        $button.prop('disabled', false).text('Unassign');
                    }
                }
            });
        },
        
        submitHourForm: function(e) {
            e.preventDefault();
            var $form = $(this);
            var formData = $form.serialize();
            
            $.ajax({
                url: lccp_dashboard.ajax_url,
                type: 'POST',
                data: formData + '&action=lccp_log_hours',
                beforeSend: function() {
                    $form.find('input[type="submit"]').prop('disabled', true).val('Submitting...');
                },
                success: function(response) {
                    if (response.success) {
                        $('#lccp-form-message')
                            .html('<div class="notice notice-success"><p>Hours logged successfully!</p></div>')
                            .fadeIn();
                        
                        // Reset form
                        $form[0].reset();
                        
                        // Update hours widget if present
                        if ($('.lccp-hours-widget').length) {
                            location.reload();
                        }
                    } else {
                        $('#lccp-form-message')
                            .html('<div class="notice notice-error"><p>' + (response.data || 'Error logging hours') + '</p></div>')
                            .fadeIn();
                    }
                },
                complete: function() {
                    $form.find('input[type="submit"]').prop('disabled', false).val('Log Hours');
                }
            });
        },
        
        deleteEntry: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this entry?')) {
                return;
            }
            
            var $button = $(this);
            var entryId = $button.data('entry-id');
            
            $.ajax({
                url: lccp_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'lccp_delete_entry',
                    entry_id: entryId,
                    nonce: lccp_dashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error deleting entry');
                    }
                }
            });
        },
        
        showModal: function(content) {
            $('<div class="modal-overlay"></div>').appendTo('body');
            $('<div class="student-details-modal">' + content + '<button class="lccp-close-modal">&times;</button></div>').appendTo('body');
        },
        
        closeModal: function() {
            $('.modal-overlay, .student-details-modal').remove();
        },
        
        showLoading: function() {
            $('body').addClass('lccp-loading');
        },
        
        hideLoading: function() {
            $('body').removeClass('lccp-loading');
        },
        
        // Helper function to format numbers
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        
        // Auto-refresh dashboard stats
        startAutoRefresh: function() {
            setInterval(function() {
                LCCPDashboard.refreshStats();
            }, 60000); // Refresh every minute
        },
        
        refreshStats: function() {
            $.ajax({
                url: lccp_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'lccp_refresh_dashboard_stats',
                    nonce: lccp_dashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update stats on the page
                        $('.lccp-dashboard-stats').html(response.data.html);
                        LCCPDashboard.initProgressBars();
                    }
                }
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        LCCPDashboard.init();
        
        // Start auto-refresh if on dashboard page
        if ($('.lccp-dashboard').length) {
            LCCPDashboard.startAutoRefresh();
        }
    });
    
    // Make available globally
    window.LCCPDashboard = LCCPDashboard;
    
})(jQuery);