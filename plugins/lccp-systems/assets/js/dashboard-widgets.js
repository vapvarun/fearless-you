/**
 * LCCP Dashboard Widgets JavaScript
 * WordPress Standard Dashboard Widget Interactions
 *
 * @package LCCP Systems
 * @since 1.1.0
 */

(function($) {
    'use strict';

    const LCCPDashboardWidgets = {
        /**
         * Initialize dashboard widgets
         */
        init: function() {
            this.bindEvents();
            this.setupRefresh();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Activity filter changes
            $(document).on('change', '#activity-filter-role, #activity-filter-time', this.filterActivity);

            // View details links
            $(document).on('click', '.view-details', this.viewDetails);

            // Refresh widgets
            $(document).on('click', '.lccp-widget-refresh', this.refreshWidget);
        },

        /**
         * Filter activity feed
         */
        filterActivity: function(e) {
            e.preventDefault();

            const role = $('#activity-filter-role').val();
            const time = $('#activity-filter-time').val();

            $.ajax({
                url: lccpDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'lccp_get_activity_feed',
                    nonce: lccpDashboard.nonce,
                    role: role,
                    time: time
                },
                beforeSend: function() {
                    $('.lccp-activity-block').html('<div class="lccp-widget-loading"><span class="spinner is-active"></span><p>Loading activity...</p></div>');
                },
                success: function(response) {
                    if (response.success) {
                        $('.lccp-activity-block').html(response.data.html);
                    } else {
                        $('.lccp-activity-block').html('<div class="lccp-empty-state"><p class="lccp-empty-state-description">Error loading activity.</p></div>');
                    }
                },
                error: function() {
                    $('.lccp-activity-block').html('<div class="lccp-empty-state"><p class="lccp-empty-state-description">Error loading activity.</p></div>');
                }
            });
        },

        /**
         * View mentor/student details
         */
        viewDetails: function(e) {
            e.preventDefault();

            const mentorId = $(this).data('mentor-id');
            const studentId = $(this).data('student-id');

            if (mentorId) {
                // Open modal or redirect to details page
                window.location.href = lccpDashboard.adminUrl + 'admin.php?page=lccp-mentor-details&mentor_id=' + mentorId;
            } else if (studentId) {
                window.location.href = lccpDashboard.adminUrl + 'admin.php?page=lccp-student-details&student_id=' + studentId;
            }
        },

        /**
         * Refresh widget data
         */
        refreshWidget: function(e) {
            e.preventDefault();

            const $widget = $(this).closest('.postbox');
            const widgetId = $widget.attr('id');

            $.ajax({
                url: lccpDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'lccp_refresh_widget',
                    nonce: lccpDashboard.nonce,
                    widget_id: widgetId
                },
                beforeSend: function() {
                    $widget.find('.inside').html('<div class="lccp-widget-loading"><span class="spinner is-active"></span><p>Refreshing...</p></div>');
                },
                success: function(response) {
                    if (response.success) {
                        $widget.find('.inside').html(response.data.html);
                    } else {
                        alert('Error refreshing widget');
                    }
                },
                error: function() {
                    alert('Error refreshing widget');
                }
            });
        },

        /**
         * Setup auto-refresh for widgets (every 5 minutes)
         */
        setupRefresh: function() {
            setInterval(function() {
                // Auto-refresh activity widget only
                if ($('#lccp_all_activity').length) {
                    $('#lccp_all_activity').find('.lccp-widget-refresh').trigger('click');
                }
            }, 300000); // 5 minutes
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        LCCPDashboardWidgets.init();
    });

    // Make available globally
    window.LCCPDashboardWidgets = LCCPDashboardWidgets;

})(jQuery);
