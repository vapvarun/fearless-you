jQuery(document).ready(function($) {
    // Handle feature toggles
    $('.lccp-events-settings input[type="checkbox"]').on('change', function() {
        var $toggle = $(this);
        var feature = '';
        
        if ($toggle.attr('name') === 'lccp_events_virtual_enabled') {
            feature = 'virtual';
        } else if ($toggle.attr('name') === 'lccp_events_blocks_enabled') {
            feature = 'blocks';
        } else if ($toggle.attr('name') === 'lccp_events_shortcodes_enabled') {
            feature = 'shortcodes';
        }
        
        if (feature) {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'lccp_toggle_event_feature',
                    feature: feature,
                    status: $toggle.prop('checked') ? 'on' : 'off',
                    _wpnonce: $('#_wpnonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Show success notification
                        var notice = $('<div class="notice notice-success is-dismissible"><p>Feature updated successfully!</p></div>');
                        $('.wrap h1').after(notice);
                        setTimeout(function() {
                            notice.fadeOut();
                        }, 3000);
                    }
                }
            });
        }
    });
});

// Function to insert shortcode in editor
function lccp_insert_shortcode() {
    var shortcode = prompt('Enter shortcode parameters:\n\nExample: limit="5" category="workshops" layout="grid"', 'limit="5"');
    if (shortcode) {
        wp.data.dispatch('core/editor').insertBlocks(
            wp.blocks.createBlock('core/shortcode', {
                text: '[lccp_events ' + shortcode + ']'
            })
        );
    }
}