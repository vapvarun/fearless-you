/**
 * SVG Animations for LCCP Systems
 * Handles animated SVG elements and progress indicators
 *
 * @package LCCP Systems
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    window.LCCP_SVG_Animations = {
        
        /**
         * Initialize SVG animations
         */
        init: function() {
            this.initProgressCircles();
            this.initArchElements();
            this.initLoadingSpinners();
            this.initSuccessAnimations();
            this.initScrollAnimations();
        },
        
        /**
         * Initialize animated progress circles
         */
        initProgressCircles: function() {
            $('.lccp-progress-circle').each(function() {
                var $circle = $(this);
                var progress = parseFloat($circle.data('progress')) || 0;
                var circumference = 2 * Math.PI * 40; // radius = 40
                var offset = circumference - (progress / 100) * circumference;
                
                // Set initial stroke-dasharray and stroke-dashoffset
                $circle.find('#progress-fill').attr({
                    'stroke-dasharray': circumference,
                    'stroke-dashoffset': circumference
                });
                
                // Animate to target progress
                setTimeout(function() {
                    $circle.find('#progress-fill').attr('stroke-dashoffset', offset);
                }, 100);
            });
        },
        
        /**
         * Initialize arch elements
         */
        initArchElements: function() {
            $('.lccp-arch').each(function() {
                var $arch = $(this);
                var delay = parseInt($arch.data('delay')) || 0;
                
                setTimeout(function() {
                    $arch.addClass('lccp-arch-animated');
                }, delay);
            });
        },
        
        /**
         * Initialize loading spinners
         */
        initLoadingSpinners: function() {
            $('.lccp-loading-spinner').each(function() {
                var $spinner = $(this);
                var duration = parseInt($spinner.data('duration')) || 1000;
                
                $spinner.find('#spinner').attr('dur', duration + 'ms');
            });
        },
        
        /**
         * Initialize success animations
         */
        initSuccessAnimations: function() {
            $('.lccp-success-checkmark').each(function() {
                var $checkmark = $(this);
                var trigger = $checkmark.data('trigger');
                
                if (trigger === 'auto') {
                    setTimeout(function() {
                        $checkmark.addClass('lccp-success-animated');
                    }, 500);
                } else if (trigger === 'click') {
                    $checkmark.on('click', function() {
                        $(this).addClass('lccp-success-animated');
                    });
                }
            });
        },
        
        /**
         * Initialize scroll-triggered animations
         */
        initScrollAnimations: function() {
            var self = this;
            
            $(window).on('scroll', function() {
                $('.lccp-scroll-animate').each(function() {
                    var $element = $(this);
                    var elementTop = $element.offset().top;
                    var elementBottom = elementTop + $element.outerHeight();
                    var viewportTop = $(window).scrollTop();
                    var viewportBottom = viewportTop + $(window).height();
                    
                    if (elementBottom > viewportTop && elementTop < viewportBottom) {
                        if (!$element.hasClass('lccp-animated')) {
                            $element.addClass('lccp-animated');
                            
                            // Trigger specific animation based on element type
                            if ($element.hasClass('lccp-progress-circle')) {
                                self.animateProgressCircle($element);
                            } else if ($element.hasClass('lccp-arch')) {
                                self.animateArch($element);
                            }
                        }
                    }
                });
            });
        },
        
        /**
         * Animate progress circle
         */
        animateProgressCircle: function($circle) {
            var progress = parseFloat($circle.data('progress')) || 0;
            var circumference = 2 * Math.PI * 40;
            var offset = circumference - (progress / 100) * circumference;
            
            $circle.find('#progress-fill').attr('stroke-dashoffset', offset);
        },
        
        /**
         * Animate arch element
         */
        animateArch: function($arch) {
            $arch.addClass('lccp-arch-animated');
        },
        
        /**
         * Create animated progress bar
         */
        createProgressBar: function(container, progress, options) {
            options = $.extend({
                width: 200,
                height: 20,
                color: '#007cba',
                backgroundColor: '#e0e0e0',
                animated: true,
                duration: 1000
            }, options);
            
            var svg = $('<svg>').attr({
                width: options.width,
                height: options.height,
                class: 'lccp-progress-bar'
            });
            
            var background = $('<rect>').attr({
                width: options.width,
                height: options.height,
                fill: options.backgroundColor,
                rx: options.height / 2,
                ry: options.height / 2
            });
            
            var fill = $('<rect>').attr({
                width: 0,
                height: options.height,
                fill: options.color,
                rx: options.height / 2,
                ry: options.height / 2
            });
            
            if (options.animated) {
                fill.append($('<animate>').attr({
                    attributeName: 'width',
                    values: '0;' + (options.width * progress / 100),
                    dur: options.duration + 'ms',
                    fill: 'freeze'
                }));
            } else {
                fill.attr('width', options.width * progress / 100);
            }
            
            svg.append(background).append(fill);
            $(container).append(svg);
            
            return svg;
        },
        
        /**
         * Create animated arch
         */
        createArch: function(container, options) {
            options = $.extend({
                width: 100,
                height: 100,
                strokeWidth: 4,
                color: '#007cba',
                animated: true,
                duration: 1500
            }, options);
            
            var svg = $('<svg>').attr({
                width: options.width,
                height: options.height,
                class: 'lccp-arch'
            });
            
            var path = $('<path>').attr({
                d: 'M 20 80 Q 50 20 80 80',
                fill: 'none',
                stroke: options.color,
                'stroke-width': options.strokeWidth,
                'stroke-linecap': 'round'
            });
            
            if (options.animated) {
                path.append($('<animate>').attr({
                    attributeName: 'stroke-dasharray',
                    values: '0,200;200,0',
                    dur: options.duration + 'ms',
                    fill: 'freeze'
                }));
            }
            
            svg.append(path);
            $(container).append(svg);
            
            return svg;
        },
        
        /**
         * Create loading spinner
         */
        createLoadingSpinner: function(container, options) {
            options = $.extend({
                size: 40,
                strokeWidth: 4,
                color: '#007cba',
                duration: 1000
            }, options);
            
            var svg = $('<svg>').attr({
                width: options.size,
                height: options.size,
                class: 'lccp-loading-spinner'
            });
            
            var circle = $('<circle>').attr({
                cx: options.size / 2,
                cy: options.size / 2,
                r: (options.size - options.strokeWidth) / 2,
                fill: 'none',
                stroke: options.color,
                'stroke-width': options.strokeWidth,
                'stroke-dasharray': Math.PI * (options.size - options.strokeWidth),
                'stroke-dashoffset': Math.PI * (options.size - options.strokeWidth)
            });
            
            circle.append($('<animateTransform>').attr({
                attributeName: 'transform',
                type: 'rotate',
                values: '0 ' + (options.size / 2) + ' ' + (options.size / 2) + ';360 ' + (options.size / 2) + ' ' + (options.size / 2),
                dur: options.duration + 'ms',
                repeatCount: 'indefinite'
            }));
            
            svg.append(circle);
            $(container).append(svg);
            
            return svg;
        },
        
        /**
         * Create success checkmark
         */
        createSuccessCheckmark: function(container, options) {
            options = $.extend({
                size: 60,
                strokeWidth: 4,
                color: '#46b450',
                animated: true,
                duration: 800
            }, options);
            
            var svg = $('<svg>').attr({
                width: options.size,
                height: options.size,
                class: 'lccp-success-checkmark'
            });
            
            var path = $('<path>').attr({
                d: 'M 15 30 L 25 40 L 45 20',
                fill: 'none',
                stroke: options.color,
                'stroke-width': options.strokeWidth,
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round'
            });
            
            if (options.animated) {
                path.append($('<animate>').attr({
                    attributeName: 'stroke-dasharray',
                    values: '100;0',
                    dur: options.duration + 'ms',
                    fill: 'freeze'
                }));
            }
            
            svg.append(path);
            $(container).append(svg);
            
            return svg;
        },
        
        /**
         * Animate element on scroll
         */
        animateOnScroll: function($element, animationType) {
            var elementTop = $element.offset().top;
            var elementBottom = elementTop + $element.outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            
            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                $element.addClass('lccp-animated');
                
                switch (animationType) {
                    case 'fadeIn':
                        $element.css('opacity', 1);
                        break;
                    case 'slideUp':
                        $element.css('transform', 'translateY(0)');
                        break;
                    case 'slideLeft':
                        $element.css('transform', 'translateX(0)');
                        break;
                    case 'scale':
                        $element.css('transform', 'scale(1)');
                        break;
                }
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        LCCP_SVG_Animations.init();
    });
    
})(jQuery);
