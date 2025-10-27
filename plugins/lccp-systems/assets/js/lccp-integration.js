/**
 * LCCP Integration JavaScript for Dasher Plugin
 */

jQuery(document).ready(function($) {
    
    // Initialize LCCP functionality
    initLCCPIntegration();
    
    function initLCCPIntegration() {
        // Auto-refresh LCCP data every 5 minutes
        if (typeof dasher_lccp !== 'undefined') {
            setInterval(function() {
                refreshLCCPCards();
            }, 300000); // 5 minutes
        }
        
        // Handle LCCP form submissions
        handleLCCPFormSubmissions();
        
        // Add LCCP-specific event handlers
        addLCCPEventHandlers();
    }
    
    /**
     * Refresh LCCP data cards
     */
    function refreshLCCPCards() {
        $('.dasher-card').each(function() {
            const $card = $(this);
            
            // Check if this is an LCCP-related card
            if ($card.find('.tier-badge').length > 0 || $card.hasClass('lccp-main-card')) {
                refreshCardData($card);
            }
        });
    }
    
    /**
     * Refresh individual card data
     */
    function refreshCardData($card) {
        const studentId = $card.data('student-id') || null;
        
        // Show loading state
        $card.addClass('loading');
        
        $.ajax({
            url: dasher_lccp.ajax_url,
            type: 'POST',
            data: {
                action: 'dasher_get_student_lccp_data',
                student_id: studentId,
                nonce: dasher_lccp.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateCardWithData($card, response.data);
                } else {
                    console.error('Failed to refresh LCCP data:', response.message);
                }
            },
            error: function() {
                console.error('AJAX error while refreshing LCCP data');
            },
            complete: function() {
                $card.removeClass('loading');
            }
        });
    }
    
    /**
     * Update card with new data
     */
    function updateCardWithData($card, data) {
        // Update hours value
        $card.find('.dasher-card-value').text(parseFloat(data.hours).toFixed(1));
        
        // Update tier badge
        const tierBadge = $card.find('.tier-badge');
        if (tierBadge.length > 0) {
            tierBadge.removeClass('tier-cflc tier-acflc tier-cft tier-mcflc')
                    .addClass('tier-' + data.tier.abbr.toLowerCase())
                    .text(data.tier.abbr);
        }
        
        // Update progress bar
        const progressFill = $card.find('.dasher-progress-fill');
        if (progressFill.length > 0) {
            progressFill.animate({
                width: data.progress_percentage + '%'
            }, 500);
        }
        
        // Update description if it contains tier information
        const description = $card.find('.dasher-card-description');
        if (description.length > 0 && description.text().includes('Working towards:')) {
            description.html('Working towards: <strong>' + data.tier.full + '</strong>');
        }
    }
    
    /**
     * Handle LCCP form submissions
     */
    function handleLCCPFormSubmissions() {
        // Enhance the existing LCCP form with AJAX
        $(document).on('submit', '#lccp-hour-tracker-form', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('input[type="submit"]');
            const originalText = $submitBtn.val();
            
            // Show loading state
            $submitBtn.val(dasher_lccp.strings.loading).prop('disabled', true);
            
            // Submit form via AJAX
            $.ajax({
                url: $form.attr('action') || window.location.href,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    // Check if submission was successful
                    if (response.includes('successfully')) {
                        showSuccessMessage(dasher_lccp.strings.hours_logged || 'Hours logged successfully!');
                        $form[0].reset(); // Clear form
                        
                        // Refresh all LCCP cards
                        setTimeout(function() {
                            refreshLCCPCards();
                        }, 1000);
                        
                        // Hide form if it was a popup
                        if ($form.closest('.lccp-log-form').is(':visible')) {
                            toggleLogForm();
                        }
                    } else {
                        showErrorMessage(dasher_lccp.strings.error_occurred || 'An error occurred. Please try again.');
                    }
                },
                error: function() {
                    showErrorMessage(dasher_lccp.strings.error_occurred || 'An error occurred. Please try again.');
                },
                complete: function() {
                    $submitBtn.val(originalText).prop('disabled', false);
                }
            });
        });
    }
    
    /**
     * Add LCCP-specific event handlers
     */
    function addLCCPEventHandlers() {
        // Handle tier badge clicks
        $(document).on('click', '.tier-badge', function(e) {
            e.preventDefault();
            showTierInfo($(this));
        });
        
        // Handle progress bar clicks
        $(document).on('click', '.dasher-progress-bar', function(e) {
            e.preventDefault();
            showProgressDetails($(this));
        });
        
        // Handle student LCCP item clicks in BigBird dashboard
        $(document).on('click', '.student-lccp-item', function(e) {
            e.preventDefault();
            expandStudentDetails($(this));
        });
        
        // Handle refresh buttons
        $(document).on('click', '[onclick="refreshLCCPData()"]', function(e) {
            e.preventDefault();
            refreshLCCPCards();
        });
    }
    
    /**
     * Show tier information modal
     */
    function showTierInfo($tierBadge) {
        const tierAbbr = $tierBadge.text();
        const tierMap = {
            'CFLC': 'Certified Fearless Living Coach (75+ hours)',
            'ACFLC': 'Advanced Certified Fearless Living Coach (150+ hours)',
            'CFT': 'Certified Fearless Trainer (250+ hours)',
            'MCFLC': 'Master Certified Fearless Living Coach (500+ hours)'
        };
        
        const tierInfo = tierMap[tierAbbr] || tierAbbr;
        
        // Create simple modal
        const modal = $('<div class="lccp-tier-modal">' +
            '<div class="modal-content">' +
            '<h3>Certification Tier</h3>' +
            '<p>' + tierInfo + '</p>' +
            '<button class="close-modal">Close</button>' +
            '</div>' +
            '</div>');
        
        $('body').append(modal);
        modal.fadeIn();
        
        // Close modal handlers
        modal.find('.close-modal').on('click', function() {
            modal.fadeOut(function() {
                modal.remove();
            });
        });
        
        modal.on('click', function(e) {
            if (e.target === this) {
                modal.fadeOut(function() {
                    modal.remove();
                });
            }
        });
    }
    
    /**
     * Show progress details
     */
    function showProgressDetails($progressBar) {
        const $card = $progressBar.closest('.dasher-card');
        const studentId = $card.data('student-id');
        
        if (studentId) {
            // Load detailed progress information
            loadStudentProgressDetails(studentId);
        }
    }
    
    /**
     * Expand student details in BigBird view
     */
    function expandStudentDetails($studentItem) {
        const studentId = $studentItem.data('student-id');
        
        if (!studentId) return;
        
        // Toggle expanded view
        if ($studentItem.hasClass('expanded')) {
            $studentItem.removeClass('expanded');
            $studentItem.find('.student-details').slideUp();
            return;
        }
        
        // Close other expanded items
        $('.student-lccp-item.expanded').removeClass('expanded')
            .find('.student-details').slideUp();
        
        // Load and show details
        loadStudentDetails(studentId, $studentItem);
    }
    
    /**
     * Load student details
     */
    function loadStudentDetails(studentId, $container) {
        $.ajax({
            url: dasher_lccp.ajax_url,
            type: 'POST',
            data: {
                action: 'dasher_get_student_lccp_data',
                student_id: studentId,
                nonce: dasher_lccp.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayStudentDetails(response.data, $container);
                }
            },
            error: function() {
                console.error('Failed to load student details');
            }
        });
    }
    
    /**
     * Display student details
     */
    function displayStudentDetails(data, $container) {
        const detailsHtml = '<div class="student-details">' +
            '<div class="details-grid">' +
            '<div class="detail-item">' +
            '<strong>Total Hours:</strong> ' + parseFloat(data.hours).toFixed(1) +
            '</div>' +
            '<div class="detail-item">' +
            '<strong>Current Tier:</strong> ' + data.tier.full +
            '</div>' +
            '<div class="detail-item">' +
            '<strong>Progress:</strong> ' + Math.round(data.progress_percentage) + '%' +
            '</div>' +
            '</div>' +
            '<div class="recent-sessions-mini">' +
            '<h5>Recent Sessions:</h5>' +
            '<div class="sessions-list">' +
            (data.recent_sessions.length > 0 ? 
                data.recent_sessions.slice(0, 3).map(function(session) {
                    return '<div class="session-item">' +
                        '<span class="session-date">' + session.session_date + '</span>' +
                        '<span class="session-hours">' + session.session_length + 'h</span>' +
                        '</div>';
                }).join('') :
                '<p>No recent sessions</p>'
            ) +
            '</div>' +
            '</div>' +
            '</div>';
        
        $container.append(detailsHtml);
        $container.addClass('expanded');
        $container.find('.student-details').slideDown();
    }
    
    /**
     * Show success message
     */
    function showSuccessMessage(message) {
        showNotification(message, 'success');
    }
    
    /**
     * Show error message
     */
    function showErrorMessage(message) {
        showNotification(message, 'error');
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        const notification = $('<div class="lccp-notification ' + type + '">' + message + '</div>');
        
        $('body').append(notification);
        notification.fadeIn();
        
        setTimeout(function() {
            notification.fadeOut(function() {
                notification.remove();
            });
        }, 4000);
    }
});

/**
 * Global functions for inline event handlers
 */
function toggleLogForm() {
    const form = document.getElementById('lccp-log-form');
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

function viewAllSessions() {
    const sessionsSection = document.querySelector('.recent-sessions-section');
    if (sessionsSection) {
        sessionsSection.scrollIntoView({ behavior: 'smooth' });
    }
}

function refreshLCCPData() {
    if (typeof jQuery !== 'undefined') {
        location.reload();
    }
}