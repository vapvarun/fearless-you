jQuery(document).ready(function($) {
    $('#ldvm-save-changes').on('click', function() {
        const button = $(this);
        const statusEl = $('.ldvm-save-status');
        const videos = [];

        $('.ldvm-video-url').each(function() {
            const row = $(this).closest('tr');
            videos.push({
                id: $(this).data('id'),
                type: $(this).data('type'),
                url: $(this).val(),
                enabled: row.find('.ldvm-video-enabled').prop('checked') ? 'on' : ''
            });
        });

        button.prop('disabled', true);
        statusEl.html('Saving...');

        $.ajax({
            url: ldvmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_video_urls',
                nonce: ldvmAjax.nonce,
                videos: videos
            },
            success: function(response) {
                if (response.success) {
                    statusEl.html('✓ Saved successfully');
                    setTimeout(() => statusEl.html(''), 3000);
                } else {
                    statusEl.html('❌ Error saving');
                }
            },
            error: function() {
                statusEl.html('❌ Error saving');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});