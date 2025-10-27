/**
 * Dasher Frontend Dashboard JavaScript
 * 
 * @package Dasher
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Force grid display on all grid containers
     */
    function forceGridDisplay() {
        $('.dasher-kpi-grid').each(function() {
            var $grid = $(this);
            // Force CSS Grid with inline styles
            $grid.css({
                'display': 'grid !important',
                'grid-template-columns': 'repeat(auto-fit, minmax(280px, 1fr))',
                'gap': '20px',
                'width': '100%'
            });
            
            // Log for debugging
            console.log('Grid container found:', $grid.length, 'Cards:', $grid.find('.dasher-kpi-card').length);
        });
        
        $('.dasher-kpi-card').each(function() {
            var $card = $(this);
            $card.css({
                'display': 'block',
                'width': 'auto',
                'float': 'none',
                'flex': 'none'
            });
        });
    }

    /**
     * Adjust grid layouts to ensure cards fill the available row space
     */
    function adjustGridLayouts() {
        // Adjust KPI grid
        $('.dasher-kpi-grid').each(function() {
            var $grid = $(this);
            var cardCount = $grid.find('.dasher-kpi-card').length;
            
            $grid.removeClass('force-1-col force-2-col force-3-col');
            
            if (cardCount === 1) {
                $grid.addClass('force-1-col');
            } else if (cardCount === 2) {
                $grid.addClass('force-2-col');
            } else if (cardCount === 3) {
                $grid.addClass('force-3-col');
            }
        });
        
        // Adjust student matrix
        $('.dasher-student-matrix').each(function() {
            var $grid = $(this);
            var cardCount = $grid.find('.dasher-student-card').length;
            
            $grid.removeClass('force-1-col force-2-col force-3-col');
            
            if (cardCount === 1) {
                $grid.addClass('force-1-col');
            } else if (cardCount === 2) {
                $grid.addClass('force-2-col');
            } else if (cardCount === 3) {
                $grid.addClass('force-3-col');
            }
        });
        
        // Adjust analytics grid
        $('.dasher-analytics-grid').each(function() {
            var $grid = $(this);
            var cardCount = $grid.find('.dasher-analytic-card').length;
            
            $grid.removeClass('force-1-col force-2-col force-3-col');
            
            if (cardCount === 1) {
                $grid.addClass('force-1-col');
            } else if (cardCount === 2) {
                $grid.addClass('force-2-col');
            } else if (cardCount === 3) {
                $grid.addClass('force-3-col');
            }
        });
    }

    $(document).ready(function() {
        // Force grid display immediately
        forceGridDisplay();
        
        // Adjust grid layouts for proper card filling
        adjustGridLayouts();
        
        // Re-adjust on window resize
        $(window).on('resize', function() {
            forceGridDisplay();
            adjustGridLayouts();
        });
        
        // Double-check after a short delay
        setTimeout(function() {
            forceGridDisplay();
            adjustGridLayouts();
        }, 100);
        
        // Handle view progress button clicks
        $('.view-progress-btn').on('click', function() {
            var studentId = $(this).data('student-id');
            
            // Show loading state
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.text('Loading...').prop('disabled', true);
            
            // AJAX call to fetch detailed progress
            $.ajax({
                url: dasher_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'dasher_get_student_progress',
                    student_id: studentId,
                    nonce: dasher_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // For now, show progress in an alert
                        // In production, you'd show this in a modal or expanded view
                        var progressData = response.data.progress;
                        var message = 'Student Progress:\n\n';
                        
                        progressData.forEach(function(course) {
                            message += course.title + ': ' + course.progress.percentage + '%\n';
                        });
                        
                        alert(message);
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to load progress'));
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                },
                complete: function() {
                    // Restore button state
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        });

        // Add smooth scroll for internal links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            var target = $(this.hash);
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 500);
            }
        });

        // Animate progress bars on scroll
        function animateProgressBars() {
            $('.dasher-progress-fill').each(function() {
                var $this = $(this);
                if (!$this.hasClass('animated') && isElementInViewport($this[0])) {
                    var width = $this.css('width');
                    $this.css('width', '0').addClass('animated');
                    setTimeout(function() {
                        $this.css('width', width);
                    }, 100);
                }
            });
        }

        // Check if element is in viewport
        function isElementInViewport(el) {
            var rect = el.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }

        // Initial animation
        animateProgressBars();

        // Animate on scroll
        $(window).on('scroll', animateProgressBars);

        // Card hover effects
        $('.dasher-card').on('mouseenter', function() {
            $(this).find('.dasher-card-icon').addClass('animate');
        }).on('mouseleave', function() {
            $(this).find('.dasher-card-icon').removeClass('animate');
        });

        // Add animation class to CSS
        $('<style>')
            .prop('type', 'text/css')
            .html(`
                .dasher-card-icon.animate {
                    animation: pulse 0.5s ease;
                }
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                    100% { transform: scale(1); }
                }
                .dasher-progress-fill {
                    transition: width 1s ease-out;
                }
            `)
            .appendTo('head');
    });

})(jQuery);