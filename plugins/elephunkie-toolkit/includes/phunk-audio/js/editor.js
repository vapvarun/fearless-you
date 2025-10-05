(function( wp ) {
    var withSelect = wp.data.withSelect;
    var MediaUpload = wp.blockEditor.MediaUpload;

    wp.hooks.addFilter(
        'editor.MediaPlaceholder',
        'my-plugin/components/media-placeholder/replace',
        function( MediaPlaceholder ) {
            return function( props ) {
                var allowedTypes = [ 'image' ];

                if ( props.allowedTypes && props.allowedTypes.length ) {
                    allowedTypes = props.allowedTypes;
                }

                return wp.element.createElement(
                    MediaUpload,
                    {
                        allowedTypes: allowedTypes,
                        onSelect: props.onSelect,
                        type: allowedTypes[0],
                        value: props.value,
                        render: function( obj ) {
                            return wp.element.createElement(
                                'div',
                                { className: 'custom-audio-block-media-upload' },
                                wp.element.createElement(
                                    'button',
                                    {
                                        className: 'button button-large',
                                        onClick: obj.open,
                                    },
                                    wp.i18n.__( 'Select Album Art' )
                                )
                            );
                        },
                    }
                );
            };
        }
    );
})( window.wp );