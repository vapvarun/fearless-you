/**
 * Admin JavaScript for Dasher
 *
 * @package Dasher
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Initialize the Dasher plugin functionality
     */
    function initDasher() {
        // The detailed toggle functionality is handled inline in the templates
        // This file can be used for additional JS functionality
        
        // Add highlight effect to items with indicators
        highlightConnectedItems();
    }
    
    /**
     * Add visual highlight to connected items in admin lists
     */
    function highlightConnectedItems() {
        if ($('.dasher-connected-indicator').length) {
            $('.dasher-connected-indicator').each(function() {
                $(this).closest('tr').addClass('dasher-connected-row');
            });
            
            // Add CSS rule dynamically for highlighted rows
            $('<style>')
                .prop('type', 'text/css')
                .html('.dasher-connected-row { background-color: rgba(255, 185, 0, 0.05); }')
                .appendTo('head');
        }
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        initDasher();
    });
    
})(jQuery); 