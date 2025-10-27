/**
 * LCCP Advanced Widgets JavaScript
 */

(function($) {
    'use strict';

    // Widget refresh functionality
    var LCCPWidgets = {
        
        init: function() {
            this.bindEvents();
            this.initProgressBars();
            this.initActivityTracking();
            this.initRefreshTimers();
        },
        
        bindEvents: function() {
            // Refresh widget buttons
            $(document).on('click', '.lccp-widget-refresh', this.refreshWidget);
            
            // Quiz detail modal
            $(document).on('click', '.quiz-details-link', this.showQuizDetails);
            
            // Assignment detail modal
            $(document).on('click', '.assignment-details-link', this.showAssignmentDetails);
            
            // Tab navigation
            $(document).on('click', '.lccp-tab', this.switchTab);
            
            // Activity tracking
            $(document).on('click', '.lccp-track-activity', this.trackActivity);
        },
        
        initProgressBars: function() {
            $('.progress-bar').each(function() {
                var $bar = $(this);
                var percentage = $bar.data('percentage') || 0;
                
                $bar.css('width', '0%');
                
                setTimeout(function() {
                    $bar.css('width', percentage + '%');
                }, 100);
            });
        },
        
        initActivityTracking: function() {
            // Track time spent on page
            var startTime = Date.now();
            
            $(window).on('beforeunload', function() {
                var timeSpent = Math.round((Date.now() - startTime) / 1000);
                
                if (timeSpent > 30) {
                    $.post(lccp_widgets.ajax_url, {
                        action: 'lccp_track_activity',
                        nonce: lccp_widgets.nonce,
                        time_spent: timeSpent,
                        page_id: lccp_widgets.page_id || 0
                    });
                }
            });
        },
        
        initRefreshTimers: function() {
            // Auto-refresh certain widgets every 5 minutes
            setInterval(function() {
                $('.lccp-auto-refresh').each(function() {
                    LCCPWidgets.refreshWidget.call(this);
                });
            }, 300000); // 5 minutes
        },
        
        refreshWidget: function(e) {
            if (e) e.preventDefault();
            
            var $widget = $(this).closest('.lccp-widget-container');
            var widgetType = $widget.data('widget-type');
            
            if (!widgetType) return;
            
            $widget.addClass('widget-loading');
            
            $.post(lccp_widgets.ajax_url, {
                action: 'lccp_refresh_widget',
                nonce: lccp_widgets.nonce,
                widget_type: widgetType
            }, function(response) {
                if (response.success && response.data.content) {
                    $widget.html(response.data.content);
                    LCCPWidgets.initProgressBars();
                }
            }).always(function() {
                $widget.removeClass('widget-loading');
            });
        },
        
        showQuizDetails: function(e) {
            e.preventDefault();
            
            var quizId = $(this).data('quiz-id');
            var userId = $(this).data('user-id') || 0;
            
            if (!quizId) return;
            
            // Create modal if it doesn't exist
            if (!$('#lccp-modal').length) {
                $('body').append(
                    '<div id="lccp-modal" class="lccp-modal">' +
                        '<div class="lccp-modal-content">' +
                            '<span class="lccp-modal-close">&times;</span>' +
                            '<div class="lccp-modal-body"></div>' +
                        '</div>' +
                    '</div>'
                );
            }
            
            var $modal = $('#lccp-modal');
            var $modalBody = $modal.find('.lccp-modal-body');
            
            $modalBody.html('<div class="lccp-loading"><div class="lccp-spinner"></div></div>');
            $modal.fadeIn();
            
            $.post(lccp_widgets.ajax_url, {
                action: 'lccp_get_quiz_details',
                nonce: lccp_widgets.nonce,
                quiz_id: quizId,
                user_id: userId
            }, function(response) {
                if (response.success && response.data) {
                    var data = response.data;
                    var html = '<h3>' + data.quiz_title + '</h3>' +
                              '<div class="quiz-details-grid">' +
                                  '<div class="detail-item">' +
                                      '<label>Attempts:</label> ' + data.attempts +
                                  '</div>' +
                                  '<div class="detail-item">' +
                                      '<label>Best Score:</label> ' + data.best_score + '%' +
                                  '</div>' +
                                  '<div class="detail-item">' +
                                      '<label>Average Score:</label> ' + data.average_score + '%' +
                                  '</div>' +
                                  '<div class="detail-item">' +
                                      '<label>Last Attempt:</label> ' + data.last_attempt +
                                  '</div>' +
                              '</div>';
                    
                    $modalBody.html(html);
                } else {
                    $modalBody.html('<p>Error loading quiz details.</p>');
                }
            });
        },
        
        showAssignmentDetails: function(e) {
            e.preventDefault();
            
            var assignmentId = $(this).data('assignment-id');
            var userId = $(this).data('user-id') || 0;
            
            if (!assignmentId) return;
            
            // Create modal if it doesn't exist
            if (!$('#lccp-modal').length) {
                $('body').append(
                    '<div id="lccp-modal" class="lccp-modal">' +
                        '<div class="lccp-modal-content">' +
                            '<span class="lccp-modal-close">&times;</span>' +
                            '<div class="lccp-modal-body"></div>' +
                        '</div>' +
                    '</div>'
                );
            }
            
            var $modal = $('#lccp-modal');
            var $modalBody = $modal.find('.lccp-modal-body');
            
            $modalBody.html('<div class="lccp-loading"><div class="lccp-spinner"></div></div>');
            $modal.fadeIn();
            
            $.post(lccp_widgets.ajax_url, {
                action: 'lccp_get_assignment_details',
                nonce: lccp_widgets.nonce,
                assignment_id: assignmentId,
                user_id: userId
            }, function(response) {
                if (response.success && response.data) {
                    var data = response.data;
                    var statusClass = data.status ? data.status.toLowerCase().replace(' ', '-') : 'pending';
                    
                    var html = '<h3>' + data.title + '</h3>' +
                              '<div class="assignment-status ' + statusClass + '">' +
                                  'Status: ' + (data.status || 'Pending') +
                              '</div>' +
                              '<div class="assignment-content">' + data.content + '</div>';
                    
                    if (data.lesson) {
                        html += '<p><strong>Lesson:</strong> ' + data.lesson + '</p>';
                    }
                    
                    if (data.submitted_date) {
                        html += '<p><strong>Submitted:</strong> ' + data.submitted_date + '</p>';
                    }
                    
                    if (data.points) {
                        html += '<p><strong>Points:</strong> ' + data.points + '</p>';
                    }
                    
                    if (data.feedback) {
                        html += '<div class="instructor-feedback">' +
                               '<h4>Instructor Feedback</h4>' +
                               '<p>' + data.feedback + '</p>' +
                               '</div>';
                    }
                    
                    $modalBody.html(html);
                } else {
                    $modalBody.html('<p>Error loading assignment details.</p>');
                }
            });
        },
        
        switchTab: function(e) {
            e.preventDefault();
            
            var $tab = $(this);
            var tabId = $tab.data('tab');
            var $container = $tab.closest('.lccp-widget-container');
            
            // Update active tab
            $container.find('.lccp-tab').removeClass('active');
            $tab.addClass('active');
            
            // Show corresponding content
            $container.find('.lccp-tab-content').hide();
            $container.find('#' + tabId).fadeIn();
        },
        
        trackActivity: function(e) {
            var activityType = $(this).data('activity-type');
            var activityId = $(this).data('activity-id');
            
            $.post(lccp_widgets.ajax_url, {
                action: 'lccp_track_activity',
                nonce: lccp_widgets.nonce,
                activity_type: activityType,
                activity_id: activityId
            });
        }
    };
    
    // Modal close functionality
    $(document).on('click', '.lccp-modal-close, .lccp-modal', function(e) {
        if (e.target === this) {
            $('#lccp-modal').fadeOut();
        }
    });
    
    // Countdown timer for live sessions
    function updateCountdowns() {
        $('.session-countdown').each(function() {
            var $countdown = $(this);
            var targetTime = new Date($countdown.data('target-time')).getTime();
            var now = new Date().getTime();
            var distance = targetTime - now;
            
            if (distance < 0) {
                $countdown.html('Session has started');
                return;
            }
            
            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            
            var countdownText = '';
            if (days > 0) {
                countdownText = days + 'd ' + hours + 'h';
            } else if (hours > 0) {
                countdownText = hours + 'h ' + minutes + 'm';
            } else {
                countdownText = minutes + ' minutes';
            }
            
            $countdown.html('Starts in ' + countdownText);
        });
    }
    
    // Chart initialization for dashboard widgets
    function initCharts() {
        $('.lccp-chart').each(function() {
            var $chart = $(this);
            var type = $chart.data('chart-type');
            var data = $chart.data('chart-data');
            
            if (!data) return;
            
            // Here you would integrate with a charting library like Chart.js
            // For now, we'll create a simple bar chart with CSS
            if (type === 'bar') {
                var maxValue = Math.max(...data.values);
                var html = '<div class="simple-bar-chart">';
                
                data.labels.forEach(function(label, index) {
                    var value = data.values[index];
                    var percentage = (value / maxValue) * 100;
                    
                    html += '<div class="chart-bar-item">' +
                           '<div class="chart-bar" style="height: ' + percentage + '%">' +
                           '<span class="chart-value">' + value + '</span>' +
                           '</div>' +
                           '<span class="chart-label">' + label + '</span>' +
                           '</div>';
                });
                
                html += '</div>';
                $chart.html(html);
            }
        });
    }
    
    // Notification system
    var LCCPNotifications = {
        show: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="lccp-notification lccp-notification-' + type + '">' +
                                 '<span class="notification-message">' + message + '</span>' +
                                 '<span class="notification-close">&times;</span>' +
                                 '</div>');
            
            $('body').append($notification);
            
            $notification.animate({
                right: '20px',
                opacity: 1
            }, 300);
            
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // Export for use in other scripts
    window.LCCPWidgets = LCCPWidgets;
    window.LCCPNotifications = LCCPNotifications;
    
    // Initialize on document ready
    $(document).ready(function() {
        LCCPWidgets.init();
        updateCountdowns();
        initCharts();
        
        // Update countdowns every minute
        setInterval(updateCountdowns, 60000);
    });
    
})(jQuery);