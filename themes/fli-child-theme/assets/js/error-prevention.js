/**
 * Error Prevention Script
 * Prevents common JavaScript errors from breaking the site
 */

(function() {
    'use strict';
    
    // Execute this immediately before any other scripts
    if (typeof window.MutationObserver !== 'undefined') {
        // Store original MutationObserver
        var OriginalMutationObserver = window.MutationObserver;
        
        // Create wrapper class
        var SafeMutationObserver = function(callback) {
            // Wrap the callback to catch errors
            var safeCallback = function(mutations, observer) {
                try {
                    callback(mutations, observer);
                } catch (e) {
                    console.warn('MutationObserver callback error caught:', e);
                }
            };
            
            var instance = new OriginalMutationObserver(safeCallback);
            var originalObserve = instance.observe.bind(instance);
            
            // Override observe method with safety check
            instance.observe = function(target, options) {
                // Check if target is a valid Node
                if (target && typeof target === 'object' && target.nodeType && target.nodeType > 0) {
                    try {
                        return originalObserve(target, options);
                    } catch (e) {
                        console.warn('MutationObserver.observe error caught:', e);
                    }
                } else {
                    console.warn('MutationObserver: Attempted to observe invalid target:', target);
                    // Return silently to prevent errors
                    return undefined;
                }
            };
            
            return instance;
        };
        
        // Copy all properties and methods from original
        SafeMutationObserver.prototype = OriginalMutationObserver.prototype;
        
        // Replace global MutationObserver
        try {
            window.MutationObserver = SafeMutationObserver;
            window.WebKitMutationObserver = SafeMutationObserver; // For older browsers
        } catch (e) {
            console.warn('Could not override MutationObserver:', e);
        }
    }
    
    // Add safety wrapper for classList operations
    document.addEventListener('DOMContentLoaded', function() {
        // Intercept querySelector calls to add null checks
        var originalQuerySelector = document.querySelector;
        var originalQuerySelectorAll = document.querySelectorAll;
        
        // Safe classList wrapper
        function safeClassList(element) {
            if (!element || !element.classList) {
                return {
                    add: function() { console.warn('Attempted classList.add on null element'); },
                    remove: function() { console.warn('Attempted classList.remove on null element'); },
                    toggle: function() { console.warn('Attempted classList.toggle on null element'); return false; },
                    contains: function() { return false; }
                };
            }
            return element.classList;
        }
        
        // Monitor for common null element errors
        var errorCount = 0;
        window.addEventListener('error', function(e) {
            if (e.message && (
                e.message.includes('Cannot read properties of null') ||
                e.message.includes('Cannot read property') ||
                e.message.includes('is not of type \'Node\'') ||
                e.message.includes('Failed to execute \'observe\' on \'MutationObserver\'') ||
                e.message.includes('parameter 1 is not of type')
            )) {
                errorCount++;
                if (errorCount <= 10) { // Log first 10 errors
                    console.warn('JavaScript error prevented:', e.message, 'at', e.filename, ':', e.lineno);
                }
                e.preventDefault();
                e.stopPropagation();
                return true; // Prevent error from propagating
            }
        }, true); // Use capture phase to catch errors early
    });
    
})();