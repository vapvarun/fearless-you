/**
 * TinyMCE Plugin for LCCP Checklist
 * Adds a button to insert checklist shortcode
 */

(function() {
    tinymce.PluginManager.add('lccp_checklist', function(editor, url) {
        
        // Add button
        editor.addButton('lccp_checklist', {
            text: '',
            icon: 'checklist',
            tooltip: 'Insert Checklist',
            onclick: function() {
                // Open dialog
                editor.windowManager.open({
                    title: 'Insert Checklist',
                    body: [
                        {
                            type: 'textbox',
                            name: 'title',
                            label: 'Checklist Title (optional)',
                            value: ''
                        },
                        {
                            type: 'textbox',
                            name: 'id',
                            label: 'Unique ID (optional)',
                            value: '',
                            tooltip: 'Leave blank to auto-generate'
                        },
                        {
                            type: 'listbox',
                            name: 'style',
                            label: 'Style',
                            values: [
                                {text: 'Default', value: 'default'},
                                {text: 'Minimal', value: 'minimal'},
                                {text: 'Boxed', value: 'boxed'}
                            ]
                        },
                        {
                            type: 'listbox',
                            name: 'save_progress',
                            label: 'Save Progress',
                            values: [
                                {text: 'Yes (for logged-in users)', value: 'yes'},
                                {text: 'No (local only)', value: 'no'}
                            ]
                        },
                        {
                            type: 'listbox',
                            name: 'show_progress',
                            label: 'Show Progress Bar',
                            values: [
                                {text: 'Yes', value: 'yes'},
                                {text: 'No', value: 'no'}
                            ]
                        },
                        {
                            type: 'textbox',
                            name: 'items',
                            label: 'Checklist Items',
                            multiline: true,
                            minHeight: 100,
                            value: '',
                            tooltip: 'Enter each item on a new line'
                        }
                    ],
                    onsubmit: function(e) {
                        var data = e.data;
                        
                        // Build shortcode attributes
                        var attrs = '';
                        
                        if (data.title) {
                            attrs += ' title="' + data.title + '"';
                        }
                        
                        if (data.id) {
                            attrs += ' id="' + data.id + '"';
                        }
                        
                        if (data.style && data.style !== 'default') {
                            attrs += ' style="' + data.style + '"';
                        }
                        
                        if (data.save_progress) {
                            attrs += ' save_progress="' + data.save_progress + '"';
                        }
                        
                        if (data.show_progress) {
                            attrs += ' show_progress="' + data.show_progress + '"';
                        }
                        
                        // Process items
                        var content = '';
                        if (data.items) {
                            var lines = data.items.split('\n');
                            if (lines.length > 0) {
                                content = '<ul>';
                                for (var i = 0; i < lines.length; i++) {
                                    var line = lines[i].trim();
                                    if (line) {
                                        content += '<li>' + line + '</li>';
                                    }
                                }
                                content += '</ul>';
                            }
                        }
                        
                        // Insert shortcode
                        var shortcode = '[lccp_checklist' + attrs + ']';
                        if (content) {
                            shortcode += content + '[/lccp_checklist]';
                        } else {
                            shortcode += '[/lccp_checklist]';
                        }
                        
                        editor.insertContent(shortcode);
                    }
                });
            }
        });
        
        // Add menu item
        editor.addMenuItem('lccp_checklist', {
            text: 'Checklist',
            icon: 'checklist',
            context: 'insert',
            onclick: function() {
                editor.buttons.lccp_checklist.onclick();
            }
        });
        
        // Add custom CSS for the icon if needed
        editor.on('init', function() {
            var cssLink = editor.dom.create('link', {
                rel: 'stylesheet',
                href: url + '/../../../../css/checklist-admin.css'
            });
            document.getElementsByTagName('head')[0].appendChild(cssLink);
        });
        
        // Handle selected text
        editor.on('BeforeSetContent', function(e) {
            if (e.content && e.content.indexOf('[lccp_checklist') !== -1) {
                // Visual representation in editor
                e.content = e.content.replace(/\[lccp_checklist([^\]]*)\]/g, function(match, attrs) {
                    return '<div class="mce-lccp-checklist" data-attrs="' + attrs + '"><span class="mce-checklist-placeholder">Checklist</span>';
                });
                
                e.content = e.content.replace(/\[\/lccp_checklist\]/g, '</div>');
            }
        });
        
        editor.on('PostProcess', function(e) {
            if (e.get) {
                // Convert back to shortcode
                e.content = e.content.replace(/<div[^>]*class="mce-lccp-checklist"[^>]*data-attrs="([^"]*)"[^>]*>.*?<\/div>/g, function(match, attrs) {
                    var content = match.match(/<ul[^>]*>.*?<\/ul>/);
                    if (content) {
                        return '[lccp_checklist' + attrs + ']' + content[0] + '[/lccp_checklist]';
                    }
                    return '[lccp_checklist' + attrs + '][/lccp_checklist]';
                });
            }
        });
    });
})();