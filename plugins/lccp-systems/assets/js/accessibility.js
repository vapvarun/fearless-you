/**
 * LCCP Accessibility JavaScript
 * Provides interactive accessibility features
 */

(function($) {
    'use strict';
    
    var LCCPAccessibility = {
        preferences: {},
        panel: null,
        toggle: null,
        readingGuide: null,
        
        init: function() {
            this.preferences = lccp_accessibility.preferences || {};
            this.panel = $('#lccp-a11y-panel');
            this.toggle = $('#lccp-a11y-toggle');
            this.readingGuide = $('#lccp-reading-guide');
            
            this.bindEvents();
            this.applyStoredPreferences();
            this.addSkipLink();
            this.enhanceKeyboardNavigation();
            this.initReadingGuide();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Toggle panel
            this.toggle.on('click', function() {
                self.togglePanel();
            });
            
            // Close panel
            $('.lccp-a11y-close').on('click', function() {
                self.closePanel();
            });
            
            // Handle accessibility options
            $('.lccp-a11y-btn').on('click', function() {
                var action = $(this).data('action');
                if (action === 'reset') {
                    self.resetAll();
                } else {
                    self.toggleFeature(action, $(this));
                }
            });
            
            // Close panel on escape
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && !self.panel.prop('hidden')) {
                    self.closePanel();
                }
            });
            
            // Close panel when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.lccp-a11y-widget').length && !self.panel.prop('hidden')) {
                    self.closePanel();
                }
            });
        },
        
        togglePanel: function() {
            if (this.panel.prop('hidden')) {
                this.openPanel();
            } else {
                this.closePanel();
            }
        },
        
        openPanel: function() {
            this.panel.prop('hidden', false);
            this.toggle.attr('aria-expanded', 'true');
            this.panel.find('.lccp-a11y-close').focus();
        },
        
        closePanel: function() {
            this.panel.prop('hidden', true);
            this.toggle.attr('aria-expanded', 'false');
            this.toggle.focus();
        },
        
        toggleFeature: function(feature, button) {
            var isActive = button.hasClass('active');
            
            if (isActive) {
                this.disableFeature(feature);
                button.removeClass('active');
                this.preferences[feature] = false;
            } else {
                this.enableFeature(feature);
                button.addClass('active');
                this.preferences[feature] = true;
            }
            
            this.savePreferences();
        },
        
        enableFeature: function(feature) {
            switch(feature) {
                case 'high-contrast':
                    $('body').addClass('lccp-a11y-high-contrast');
                    break;
                    
                case 'font-size':
                    $('body').addClass('lccp-a11y-font-size');
                    break;
                    
                case 'readable-font':
                    $('body').addClass('lccp-a11y-readable-font');
                    break;
                    
                case 'highlight-links':
                    $('body').addClass('lccp-a11y-highlight-links');
                    break;
                    
                case 'keyboard-nav':
                    $('body').addClass('lccp-a11y-keyboard-nav');
                    this.enhanceKeyboardNavigation();
                    break;
                    
                case 'screen-reader':
                    $('body').addClass('lccp-a11y-screen-reader');
                    this.announceToScreenReader('Screen reader mode enabled');
                    break;
                    
                case 'no-animations':
                    $('body').addClass('lccp-a11y-no-animations');
                    break;
                    
                case 'reading-guide':
                    this.enableReadingGuide();
                    break;
                    
                case 'text-spacing':
                    $('body').addClass('lccp-a11y-text-spacing');
                    break;
                    
                case 'large-cursor':
                    $('body').addClass('lccp-a11y-large-cursor');
                    break;
            }
        },
        
        disableFeature: function(feature) {
            switch(feature) {
                case 'high-contrast':
                    $('body').removeClass('lccp-a11y-high-contrast');
                    break;
                    
                case 'font-size':
                    $('body').removeClass('lccp-a11y-font-size');
                    break;
                    
                case 'readable-font':
                    $('body').removeClass('lccp-a11y-readable-font');
                    break;
                    
                case 'highlight-links':
                    $('body').removeClass('lccp-a11y-highlight-links');
                    break;
                    
                case 'keyboard-nav':
                    $('body').removeClass('lccp-a11y-keyboard-nav');
                    break;
                    
                case 'screen-reader':
                    $('body').removeClass('lccp-a11y-screen-reader');
                    this.announceToScreenReader('Screen reader mode disabled');
                    break;
                    
                case 'no-animations':
                    $('body').removeClass('lccp-a11y-no-animations');
                    break;
                    
                case 'reading-guide':
                    this.disableReadingGuide();
                    break;
                    
                case 'text-spacing':
                    $('body').removeClass('lccp-a11y-text-spacing');
                    break;
                    
                case 'large-cursor':
                    $('body').removeClass('lccp-a11y-large-cursor');
                    break;
            }
        },
        
        resetAll: function() {
            var self = this;
            
            // Remove all accessibility classes
            $('body').removeClass(function(index, className) {
                return (className.match(/\blccp-a11y-\S+/g) || []).join(' ');
            });
            
            // Reset all buttons
            $('.lccp-a11y-btn').removeClass('active');
            
            // Clear preferences
            this.preferences = {};
            this.savePreferences();
            
            // Disable reading guide
            this.disableReadingGuide();
            
            this.announceToScreenReader('All accessibility settings have been reset');
        },
        
        applyStoredPreferences: function() {
            var self = this;
            
            $.each(this.preferences, function(feature, enabled) {
                if (enabled) {
                    self.enableFeature(feature);
                    $('.lccp-a11y-btn[data-action="' + feature + '"]').addClass('active');
                }
            });
        },
        
        savePreferences: function() {
            $.post(lccp_accessibility.ajax_url, {
                action: 'save_accessibility_preferences',
                nonce: lccp_accessibility.nonce,
                preferences: this.preferences
            });
        },
        
        addSkipLink: function() {
            if ($('.skip-link').length === 0) {
                var skipLink = $('<a href="#main" class="skip-link">Skip to main content</a>');
                $('body').prepend(skipLink);
            }
        },
        
        enhanceKeyboardNavigation: function() {
            // Add tabindex to important elements that might be missing it
            $('a, button, input, select, textarea, [role="button"], [role="link"]').each(function() {
                if (!$(this).attr('tabindex') && !$(this).is(':input')) {
                    $(this).attr('tabindex', '0');
                }
            });
            
            // Handle keyboard navigation for custom elements
            $(document).on('keydown', '[role="button"], [role="link"]', function(e) {
                if (e.keyCode === 13 || e.keyCode === 32) { // Enter or Space
                    e.preventDefault();
                    $(this).click();
                }
            });
        },
        
        initReadingGuide: function() {
            var self = this;
            
            $(document).on('mousemove', function(e) {
                if (self.preferences['reading-guide']) {
                    self.readingGuide.css('top', e.pageY - 10);
                }
            });
        },
        
        enableReadingGuide: function() {
            this.readingGuide.prop('hidden', false);
            $('body').addClass('lccp-a11y-reading-guide');
        },
        
        disableReadingGuide: function() {
            this.readingGuide.prop('hidden', true);
            $('body').removeClass('lccp-a11y-reading-guide');
        },
        
        announceToScreenReader: function(message) {
            var announcement = $('<div class="screen-reader-text" role="alert" aria-live="assertive"></div>').text(message);
            $('body').append(announcement);
            
            setTimeout(function() {
                announcement.remove();
            }, 1000);
        },
        
        // Helper function for responsive behavior
        isMobile: function() {
            return window.innerWidth <= 768;
        },
        
        isTablet: function() {
            return window.innerWidth > 768 && window.innerWidth <= 1024;
        },
        
        isDesktop: function() {
            return window.innerWidth > 1024;
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        LCCPAccessibility.init();
        
        // Handle responsive behavior
        var resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // Adjust accessibility widget position on mobile
                if (LCCPAccessibility.isMobile()) {
                    $('.lccp-a11y-widget').removeClass('bottom-right').addClass('bottom-left');
                }
            }, 250);
        });
        
        // Ensure focus is visible
        $(document).on('focus', '*', function() {
            $(this).addClass('has-focus');
        }).on('blur', '*', function() {
            $(this).removeClass('has-focus');
        });
        
        // Handle BuddyBoss theme integration
        if ($('body').hasClass('buddyboss-theme')) {
            // Ensure BuddyBoss mobile menu is accessible
            $('.bb-mobile-menu-button').attr('aria-label', 'Open mobile menu');
            
            // Add aria labels to BuddyBoss elements
            $('.bb-header-buttons .button').each(function() {
                if (!$(this).attr('aria-label')) {
                    $(this).attr('aria-label', $(this).text());
                }
            });
        }
        
        // Handle LearnDash integration
        if (typeof learndash_data !== 'undefined') {
            // Ensure LearnDash buttons are accessible
            $('.learndash_mark_complete_button').attr('aria-label', 'Mark this lesson as complete');
            $('.learndash_quiz_button').attr('aria-label', 'Start quiz');
            
            // Add keyboard navigation for LearnDash elements
            $('.ld-course-list-items .ld-course-list-item').attr('tabindex', '0');
        }
    });
    
    // Export for external use
    window.LCCPAccessibility = LCCPAccessibility;
    
})(jQuery);