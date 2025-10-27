/**
 * LCCP Systems Frontend JavaScript
 * 
 * @package LCCP_Systems
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Initialize LCCP Systems frontend functionality
     */
    function initLCCPSystems() {
        // Initialize hour tracker form
        initHourTrackerForm();
        
        // Ensure LearnDash mark complete button works
        preserveLearnDashFunctionality();
    }
    
    /**
     * Initialize hour tracker form functionality
     */
    function initHourTrackerForm() {
        $('#lccp-hour-tracker-form').on('submit', function(e) {
            var $form = $(this);
            var $submitBtn = $form.find('input[type="submit"]');
            
            // Validate form
            if (!validateHourTrackerForm($form)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            $submitBtn.prop('disabled', true).val('Submitting...');
        });
    }
    
    /**
     * Validate hour tracker form
     */
    function validateHourTrackerForm($form) {
        var isValid = true;
        var errors = [];
        
        // Check date
        var sessionDate = $form.find('#session_date').val();
        if (!sessionDate) {
            errors.push('Please select a session date');
            isValid = false;
        }
        
        // Check client name
        var clientName = $form.find('#client_name').val();
        if (!clientName || clientName.trim().length < 2) {
            errors.push('Please enter a valid client name');
            isValid = false;
        }
        
        // Check session length
        if (!$form.find('input[name="session_length"]:checked').val()) {
            errors.push('Please select a session length');
            isValid = false;
        }
        
        // Check session number
        var sessionNumber = parseInt($form.find('#session_number').val());
        if (!sessionNumber || sessionNumber < 1 || sessionNumber > 12) {
            errors.push('Session number must be between 1 and 12');
            isValid = false;
        }
        
        // Display errors if any
        if (!isValid && errors.length > 0) {
            alert('Please correct the following errors:\n\n' + errors.join('\n'));
        }
        
        return isValid;
    }
    
    /**
     * Preserve LearnDash mark complete button functionality
     */
    function preserveLearnDashFunctionality() {
        // Ensure we don't interfere with LearnDash events
        if (typeof learndash_data !== 'undefined') {
            // Remove any conflicting event handlers
            $(document).off('click.lccp', '.learndash_mark_complete_button');
            $(document).off('submit.lccp', '#sfwd-mark-complete');
            
            // Make sure LearnDash AJAX works properly
            $(document).on('learndash_video_disable_assets', function() {
                // Don't interfere with LearnDash video progression
                return true;
            });
        }
    }
    
    /**
     * Initialize on DOM ready
     */
    $(document).ready(function() {
        initLCCPSystems();
    });
    
    /**
     * Re-initialize on AJAX complete (for dynamic content)
     */
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Only reinitialize for relevant AJAX calls
        if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
            preserveLearnDashFunctionality();
        }
    });
    
})(jQuery);