/**
 * Dasher Dashboard Customizer JavaScript
 * Frontend dashboard customization functionality
 * 
 * @package Dasher
 * @since 1.0.3
 */

(function($) {
    'use strict';
    
    let isCustomizerOpen = false;
    let originalSettings = {};
    
    $(document).ready(function() {
        initializeCustomizer();
        loadUserSettings();
    });
    
    /**
     * Initialize customizer functionality
     */
    function initializeCustomizer() {
        // Open customizer
        $(document).on('click', '#dasher-open-customizer', function(e) {
            e.preventDefault();
            openCustomizer();
        });
        
        // Close customizer
        $(document).on('click', '.dasher-customizer-close, .dasher-customizer-overlay', function(e) {
            e.preventDefault();
            closeCustomizer();
        });
        
        // Card toggle
        $(document).on('change', '.dasher-card-toggle', function() {
            const cardId = $(this).data('card-id');
            const enabled = $(this).is(':checked');
            toggleCard(cardId, enabled);
        });
        
        // Card size change
        $(document).on('change', '.dasher-card-size', function() {
            const cardId = $(this).data('card-id');
            const size = $(this).val();
            updateCardSize(cardId, size);
        });
        
        // Save settings
        $(document).on('click', '#dasher-save-settings', function(e) {
            e.preventDefault();
            saveAllSettings();
        });
        
        // Reset dashboard
        $(document).on('click', '#dasher-reset-dashboard', function(e) {
            e.preventDefault();
            if (confirm(dasher_customizer.strings.confirm_reset)) {
                resetDashboard();
            }
        });
        
        // Make card list sortable
        if ($('#dasher-card-list').length) {
            $('#dasher-card-list').sortable({
                handle: '.dasher-drag-handle',
                placeholder: 'dasher-card-placeholder',
                update: function(event, ui) {
                    updateCardOrder();
                }
            });
        }
        
        // ESC key to close
        $(document).on('keyup', function(e) {
            if (e.keyCode === 27 && isCustomizerOpen) {
                closeCustomizer();
            }
        });
    }
    
    /**
     * Load user settings and apply them
     */
    function loadUserSettings() {
        if (typeof window.dasher_dashboard_type === 'undefined') {
            return;
        }
        
        $.ajax({
            url: dasher_customizer.ajax_url,
            type: 'POST',
            data: {
                action: 'dasher_get_card_settings',
                dashboard_type: window.dasher_dashboard_type,
                nonce: dasher_customizer.nonce
            },
            success: function(response) {
                if (response.success) {
                    originalSettings = response.data;
                    applyCardSettings(response.data);
                }
            }
        });
    }
    
    /**
     * Apply card settings to the dashboard
     */
    function applyCardSettings(settings) {
        Object.keys(settings).forEach(function(cardId) {
            const cardSettings = settings[cardId];
            const $card = $(`.dasher-kpi-card[data-card-id="${cardId}"]`);
            
            if ($card.length) {
                // Apply enabled/disabled state
                if (cardSettings.enabled) {
                    $card.show().removeClass('dasher-card-disabled');
                } else {
                    $card.hide().addClass('dasher-card-disabled');
                }
                
                // Apply size
                $card.removeClass('dasher-card-small dasher-card-medium dasher-card-large')
                     .addClass(`dasher-card-${cardSettings.size}`);
                
                // Apply order
                $card.css('order', cardSettings.order);
            }
        });
        
        // Trigger grid layout adjustment
        if (typeof adjustGridLayouts === 'function') {
            adjustGridLayouts();
        }
    }
    
    /**
     * Open customizer panel
     */
    function openCustomizer() {
        $('#dasher-customizer-panel').fadeIn(300);
        $('body').addClass('dasher-customizer-open');
        isCustomizerOpen = true;
    }
    
    /**
     * Close customizer panel
     */
    function closeCustomizer() {
        $('#dasher-customizer-panel').fadeOut(300);
        $('body').removeClass('dasher-customizer-open');
        isCustomizerOpen = false;
    }
    
    /**
     * Toggle card enabled/disabled
     */
    function toggleCard(cardId, enabled) {
        $.ajax({
            url: dasher_customizer.ajax_url,
            type: 'POST',
            data: {
                action: 'dasher_toggle_card',
                dashboard_type: window.dasher_dashboard_type,
                card_id: cardId,
                enabled: enabled,
                nonce: dasher_customizer.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Apply change immediately
                    const $card = $(`.dasher-kpi-card[data-card-id="${cardId}"]`);
                    if (enabled) {
                        $card.fadeIn(300).removeClass('dasher-card-disabled');
                    } else {
                        $card.fadeOut(300).addClass('dasher-card-disabled');
                    }
                    
                    // Trigger grid layout adjustment
                    setTimeout(function() {
                        if (typeof adjustGridLayouts === 'function') {
                            adjustGridLayouts();
                        }
                    }, 300);
                    
                    showNotification(response.data.message, 'success');
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Connection error. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Update card size
     */
    function updateCardSize(cardId, size) {
        $.ajax({
            url: dasher_customizer.ajax_url,
            type: 'POST',
            data: {
                action: 'dasher_update_card_size',
                dashboard_type: window.dasher_dashboard_type,
                card_id: cardId,
                size: size,
                nonce: dasher_customizer.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Apply change immediately
                    const $card = $(`.dasher-kpi-card[data-card-id="${cardId}"]`);
                    $card.removeClass('dasher-card-small dasher-card-medium dasher-card-large')
                         .addClass(`dasher-card-${size}`);
                    
                    // Trigger grid layout adjustment
                    if (typeof adjustGridLayouts === 'function') {
                        adjustGridLayouts();
                    }
                    
                    showNotification(response.data.message, 'success');
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Connection error. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Update card order after drag and drop
     */
    function updateCardOrder() {
        const cardOrder = [];
        $('#dasher-card-list .dasher-card-setting').each(function() {
            cardOrder.push($(this).data('card-id'));
        });
        
        $.ajax({
            url: dasher_customizer.ajax_url,
            type: 'POST',
            data: {
                action: 'dasher_reorder_cards',
                dashboard_type: window.dasher_dashboard_type,
                card_order: cardOrder,
                nonce: dasher_customizer.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Apply order to actual cards
                    cardOrder.forEach(function(cardId, index) {
                        const $card = $(`.dasher-kpi-card[data-card-id="${cardId}"]`);
                        $card.css('order', index + 1);
                    });
                    
                    showNotification(response.data.message, 'success');
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Connection error. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Save all settings
     */
    function saveAllSettings() {
        const settings = {};
        
        $('#dasher-card-list .dasher-card-setting').each(function(index) {
            const cardId = $(this).data('card-id');
            const $toggle = $(this).find('.dasher-card-toggle');
            const $size = $(this).find('.dasher-card-size');
            const $title = $(this).find('.dasher-card-title');
            const $type = $(this).find('.dasher-card-type');
            
            settings[cardId] = {
                title: $title.text(),
                enabled: $toggle.is(':checked'),
                size: $size.val(),
                order: index + 1,
                type: $type.text().toLowerCase()
            };
        });
        
        $.ajax({
            url: dasher_customizer.ajax_url,
            type: 'POST',
            data: {
                action: 'dasher_save_card_settings',
                dashboard_type: window.dasher_dashboard_type,
                settings: JSON.stringify(settings),
                nonce: dasher_customizer.nonce
            },
            beforeSend: function() {
                $('#dasher-save-settings').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin"></i> ' + 'Saving...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification(dasher_customizer.strings.save_success, 'success');
                    closeCustomizer();
                } else {
                    showNotification(response.data.message || dasher_customizer.strings.save_error, 'error');
                }
            },
            error: function() {
                showNotification(dasher_customizer.strings.save_error, 'error');
            },
            complete: function() {
                $('#dasher-save-settings').prop('disabled', false)
                    .html('<i class="fas fa-save"></i> ' + 'Save Settings');
            }
        });
    }
    
    /**
     * Reset dashboard to defaults
     */
    function resetDashboard() {
        $.ajax({
            url: dasher_customizer.ajax_url,
            type: 'POST',
            data: {
                action: 'dasher_reset_dashboard',
                dashboard_type: window.dasher_dashboard_type,
                nonce: dasher_customizer.nonce
            },
            beforeSend: function() {
                $('#dasher-reset-dashboard').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin"></i> ' + 'Resetting...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification(dasher_customizer.strings.reset_success, 'success');
                    // Reload page to apply defaults
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Reset failed. Please try again.', 'error');
            },
            complete: function() {
                $('#dasher-reset-dashboard').prop('disabled', false)
                    .html('<i class="fas fa-undo"></i> ' + 'Reset to Defaults');
            }
        });
    }
    
    /**
     * Show notification message
     */
    function showNotification(message, type) {
        const $notification = $(`
            <div class="dasher-notification dasher-notification-${type}">
                <div class="dasher-notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                    <span>${message}</span>
                </div>
            </div>
        `);
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.addClass('dasher-notification-show');
        }, 100);
        
        setTimeout(function() {
            $notification.removeClass('dasher-notification-show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);