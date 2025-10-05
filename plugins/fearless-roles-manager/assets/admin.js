jQuery(document).ready(function($) {
    
    // Save role settings
    $('.frm-save-role').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var roleKey = button.data('role');
        var roleCard = button.closest('.frm-role-card');
        var statusSpan = roleCard.find('.frm-save-status');
        
        // Gather settings
        var settings = {
            wp_fusion_tags: [],
            dashboard_page: '',
            description: ''
        };
        
        // Get selected WP Fusion tags from the new input format
        var selectedTags = [];
        roleCard.find('.frm-selected-tags .frm-tag-item').each(function() {
            selectedTags.push($(this).data('tag-id'));
        });
        settings.wp_fusion_tags = selectedTags;
        
        // Get dashboard page
        settings.dashboard_page = roleCard.find('.frm-dashboard-select').val();
        
        // Get description
        settings.description = roleCard.find('.frm-role-description').val();
        
        // Show loading state
        button.prop('disabled', true).addClass('frm-loading');
        statusSpan.removeClass('show error').text('');
        
        // Send AJAX request
        $.ajax({
            url: frm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'frm_save_role_settings',
                nonce: frm_ajax.nonce,
                settings: {
                    [roleKey]: settings
                }
            },
            success: function(response) {
                if (response.success) {
                    statusSpan.text('Settings saved successfully!').addClass('show');
                    setTimeout(function() {
                        statusSpan.removeClass('show');
                    }, 3000);
                } else {
                    statusSpan.text('Error saving settings').addClass('show error');
                }
            },
            error: function() {
                statusSpan.text('Error saving settings').addClass('show error');
            },
            complete: function() {
                button.prop('disabled', false).removeClass('frm-loading');
            }
        });
    });
    
    // Toggle capabilities view
    $('.frm-toggle-caps').on('click', function(e) {
        e.preventDefault();
        
        var roleKey = $(this).data('role');
        var modal = $('#frm-caps-modal');
        var capsList = $('#frm-modal-caps-list');
        
        // Show loading
        capsList.html('<p>Loading capabilities...</p>');
        modal.show();
        
        // Get capabilities via AJAX
        $.ajax({
            url: frm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'frm_get_role_capabilities',
                nonce: frm_ajax.nonce,
                role: roleKey
            },
            success: function(response) {
                if (response.success && response.data) {
                    var html = '<ul>';
                    var caps = Object.keys(response.data).sort();
                    
                    if (caps.length === 0) {
                        html = '<p>No capabilities found for this role.</p>';
                    } else {
                        caps.forEach(function(cap) {
                            if (response.data[cap]) {
                                html += '<li>' + cap + '</li>';
                            }
                        });
                        html += '</ul>';
                    }
                    
                    capsList.html(html);
                } else {
                    capsList.html('<p>Error loading capabilities.</p>');
                }
            },
            error: function() {
                capsList.html('<p>Error loading capabilities.</p>');
            }
        });
    });
    
    // Close modal
    $('.frm-modal-close, .frm-modal').on('click', function(e) {
        if (e.target === this) {
            $('.frm-modal').hide();
        }
    });
    
    // Initialize WP Fusion Tags autocomplete
    initializeWPFusionTags();
    
    function initializeWPFusionTags() {
        $('.frm-tags-input').each(function() {
            var input = $(this);
            var roleKey = input.data('role');
            var dropdown = input.siblings('.frm-tags-dropdown');
            var selectedTagsContainer = $('.frm-selected-tags[data-role="' + roleKey + '"]');
            var allTags = frm_ajax.wp_fusion_tags || {};
            
            // Input event handler
            input.on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                
                if (searchTerm.length < 2) {
                    dropdown.hide();
                    return;
                }
                
                // Filter tags based on search term
                var filteredTags = {};
                $.each(allTags, function(tagId, tagLabel) {
                    if (tagLabel.toLowerCase().indexOf(searchTerm) > -1) {
                        // Check if tag is already selected
                        var isSelected = selectedTagsContainer.find('.frm-tag-item[data-tag-id="' + tagId + '"]').length > 0;
                        if (!isSelected) {
                            filteredTags[tagId] = tagLabel;
                        }
                    }
                });
                
                // Show dropdown with filtered results
                showTagsDropdown(dropdown, filteredTags, input, selectedTagsContainer);
            });
            
            // Focus event handler
            input.on('focus', function() {
                if ($(this).val().length >= 2) {
                    dropdown.show();
                }
            });
            
            // Blur event handler (with delay to allow clicking on dropdown items)
            input.on('blur', function() {
                setTimeout(function() {
                    dropdown.hide();
                }, 200);
            });
            
            // Remove tag functionality
            selectedTagsContainer.on('click', '.frm-remove-tag', function(e) {
                e.preventDefault();
                var tagId = $(this).data('tag-id');
                $(this).closest('.frm-tag-item').remove();
                triggerAutoSave(roleKey);
            });
        });
    }
    
    function showTagsDropdown(dropdown, filteredTags, input, selectedTagsContainer) {
        var html = '';
        
        if (Object.keys(filteredTags).length === 0) {
            html = '<div class="frm-tags-dropdown-item">No tags found</div>';
        } else {
            $.each(filteredTags, function(tagId, tagLabel) {
                html += '<div class="frm-tags-dropdown-item" data-tag-id="' + tagId + '" data-tag-label="' + tagLabel + '">' + tagLabel + '</div>';
            });
        }
        
        dropdown.html(html).show();
        
        // Click handler for dropdown items
        dropdown.find('.frm-tags-dropdown-item').on('click', function() {
            var tagId = $(this).data('tag-id');
            var tagLabel = $(this).data('tag-label');
            
            if (tagId && tagLabel) {
                // Add tag to selected tags
                var tagHtml = '<span class="frm-tag-item" data-tag-id="' + tagId + '">' +
                             tagLabel +
                             '<button type="button" class="frm-remove-tag" data-tag-id="' + tagId + '">×</button>' +
                             '</span>';
                
                selectedTagsContainer.append(tagHtml);
                
                // Clear input and hide dropdown
                input.val('');
                dropdown.hide();
                
                // Trigger auto-save
                triggerAutoSave(input.data('role'));
            }
        });
    }
    
    function triggerAutoSave(roleKey) {
        var roleCard = $('.frm-role-card[data-role="' + roleKey + '"]');
        var saveButton = roleCard.find('.frm-save-role');
        var statusSpan = roleCard.find('.frm-save-status');
        
        // Show pending status
        statusSpan.text('Changes pending...').removeClass('error').addClass('show');
        
        // Auto-save after a short delay
        setTimeout(function() {
            saveButton.trigger('click');
        }, 1000);
    }
    
    // Auto-save on change (optional)
    var autoSaveTimeout;
    $('.frm-tags-select, .frm-dashboard-select, .frm-role-description').on('change input', function() {
        var roleCard = $(this).closest('.frm-role-card');
        var saveButton = roleCard.find('.frm-save-role');
        var statusSpan = roleCard.find('.frm-save-status');
        
        // Clear previous timeout
        clearTimeout(autoSaveTimeout);
        
        // Show pending status
        statusSpan.text('Changes pending...').removeClass('error').addClass('show');
        
        // Set new timeout for auto-save
        autoSaveTimeout = setTimeout(function() {
            saveButton.trigger('click');
        }, 2000); // Auto-save after 2 seconds of inactivity
    });
    
    // Expand/collapse role cards
    $('.frm-role-header').on('click', function(e) {
        if (!$(e.target).is('.frm-role-key')) {
            var roleBody = $(this).next('.frm-role-body');
            roleBody.slideToggle(300);
            $(this).toggleClass('collapsed');
        }
    });
    
    // Search/filter roles
    if ($('.frm-roles-container').length) {
        var searchHtml = '<div class="frm-search-box">' +
                        '<input type="text" id="frm-role-search" placeholder="Search roles...">' +
                        '</div>';
        $('.frm-admin-wrap h1').after(searchHtml);
        
        $('#frm-role-search').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            
            $('.frm-role-card').each(function() {
                var roleName = $(this).find('.frm-role-header h2').text().toLowerCase();
                var roleKey = $(this).data('role').toLowerCase();
                
                if (roleName.indexOf(searchTerm) > -1 || roleKey.indexOf(searchTerm) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }
    
    // Add role statistics
    function updateRoleStats() {
        var totalRoles = $('.frm-role-card').length;
        var rolesWithTags = $('.frm-tags-select').filter(function() {
            return $(this).val() && $(this).val().length > 0;
        }).length;
        var rolesWithDashboard = $('.frm-dashboard-select').filter(function() {
            return $(this).val() !== '';
        }).length;
        
        var statsHtml = '<div class="frm-stats">' +
                       '<span>Total Roles: <strong>' + totalRoles + '</strong></span>' +
                       '<span>Roles with WP Fusion Tags: <strong>' + rolesWithTags + '</strong></span>' +
                       '<span>Roles with Custom Dashboard: <strong>' + rolesWithDashboard + '</strong></span>' +
                       '</div>';
        
        if (!$('.frm-stats').length) {
            $('.frm-notice').after(statsHtml);
        } else {
            $('.frm-stats').replaceWith(statsHtml);
        }
    }
    
    // Update stats on page load
    updateRoleStats();
    
    // Update stats when settings change
    $('.frm-save-role').on('click', function() {
        setTimeout(updateRoleStats, 100);
    });
    
    // Export/Import settings
    var exportImportHtml = '<div class="frm-export-import">' +
                          '<button class="button frm-export-settings">Export Settings</button>' +
                          '<button class="button frm-import-settings">Import Settings</button>' +
                          '<input type="file" id="frm-import-file" style="display:none;" accept=".json">' +
                          '</div>';
    $('.frm-admin-wrap h1').after(exportImportHtml);
    
    // Export settings
    $('.frm-export-settings').on('click', function() {
        var settings = {};
        
        $('.frm-role-card').each(function() {
            var roleKey = $(this).data('role');
            var selectedTags = [];
            $(this).find('.frm-selected-tags .frm-tag-item').each(function() {
                selectedTags.push($(this).data('tag-id'));
            });
            
            var roleSettings = {
                wp_fusion_tags: selectedTags,
                dashboard_page: $(this).find('.frm-dashboard-select').val(),
                description: $(this).find('.frm-role-description').val()
            };
            settings[roleKey] = roleSettings;
        });
        
        var dataStr = JSON.stringify(settings, null, 2);
        var dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
        
        var exportFileDefaultName = 'role-settings-' + new Date().toISOString().slice(0,10) + '.json';
        
        var linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportFileDefaultName);
        linkElement.click();
    });
    
    // Import settings
    $('.frm-import-settings').on('click', function() {
        $('#frm-import-file').trigger('click');
    });
    
    $('#frm-import-file').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var settings = JSON.parse(e.target.result);
                
                // Apply settings to each role
                $.each(settings, function(roleKey, roleSettings) {
                    var roleCard = $('.frm-role-card[data-role="' + roleKey + '"]');
                    if (roleCard.length) {
                        if (roleSettings.wp_fusion_tags && Array.isArray(roleSettings.wp_fusion_tags)) {
                            var selectedTagsContainer = roleCard.find('.frm-selected-tags');
                            selectedTagsContainer.empty();
                            
                            roleSettings.wp_fusion_tags.forEach(function(tagId) {
                                var tagLabel = frm_ajax.wp_fusion_tags[tagId];
                                if (tagLabel) {
                                    var tagHtml = '<span class="frm-tag-item" data-tag-id="' + tagId + '">' +
                                                 tagLabel +
                                                 '<button type="button" class="frm-remove-tag" data-tag-id="' + tagId + '">×</button>' +
                                                 '</span>';
                                    selectedTagsContainer.append(tagHtml);
                                }
                            });
                        }
                        if (roleSettings.dashboard_page !== undefined) {
                            roleCard.find('.frm-dashboard-select').val(roleSettings.dashboard_page);
                        }
                        if (roleSettings.description !== undefined) {
                            roleCard.find('.frm-role-description').val(roleSettings.description);
                        }
                    }
                });
                
                alert('Settings imported successfully! Please save each role to apply changes.');
            } catch(err) {
                alert('Error importing settings: Invalid file format');
            }
        };
        reader.readAsText(file);
    });
    
    // Save visibility settings
    $('#frm-visibility-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var button = form.find('button[type="submit"]');
        var statusSpan = form.find('.frm-save-status');
        
        var visibilitySettings = {};
        form.find('input[name^="visibility"]').each(function() {
            var roleKey = $(this).data('role');
            visibilitySettings[roleKey] = $(this).is(':checked');
        });
        
        // Show loading state
        button.prop('disabled', true).addClass('frm-loading');
        statusSpan.removeClass('show error').text('');
        
        // Send AJAX request
        $.ajax({
            url: frm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'frm_save_role_visibility',
                nonce: frm_ajax.nonce,
                visibility: visibilitySettings
            },
            success: function(response) {
                if (response.success) {
                    statusSpan.text('Visibility settings saved successfully!').addClass('show');
                    setTimeout(function() {
                        statusSpan.removeClass('show');
                    }, 3000);
                } else {
                    statusSpan.text('Error saving visibility settings').addClass('show error');
                }
            },
            error: function() {
                statusSpan.text('Error saving visibility settings').addClass('show error');
            },
            complete: function() {
                button.prop('disabled', false).removeClass('frm-loading');
            }
        });
    });
    
    // Save category assignments
    $('#frm-category-assignments-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var button = form.find('button[type="submit"]');
        var statusSpan = form.find('.frm-save-status');
        
        var assignments = {};
        form.find('select[name^="category_assignments"]').each(function() {
            var roleKey = $(this).attr('name').match(/\[([^\]]+)\]/)[1];
            assignments[roleKey] = $(this).val();
        });
        
        // Show loading state
        button.prop('disabled', true).addClass('frm-loading');
        statusSpan.removeClass('show error').text('');
        
        // Send AJAX request
        $.ajax({
            url: frm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'frm_save_category_assignments',
                nonce: frm_ajax.nonce,
                category_assignments: assignments
            },
            success: function(response) {
                if (response.success) {
                    statusSpan.text('Category assignments saved successfully!').addClass('show');
                    setTimeout(function() {
                        statusSpan.removeClass('show');
                    }, 3000);
                } else {
                    statusSpan.text('Error saving category assignments').addClass('show error');
                }
            },
            error: function() {
                statusSpan.text('Error saving category assignments').addClass('show error');
            },
            complete: function() {
                button.prop('disabled', false).removeClass('frm-loading');
            }
        });
    });
    
    // Category filtering
    $('.frm-category-stat-card').on('click', function() {
        var categoryKey = $(this).data('category');
        if (!categoryKey) return;
        
        // Toggle category visibility
        var categorySection = $('.frm-category-section[data-category="' + categoryKey + '"]');
        categorySection.slideToggle(300);
        
        // Update card appearance
        $(this).toggleClass('active');
    });
    
    // Search functionality for categories
    if ($('.frm-roles-by-category').length) {
        var searchHtml = '<div class="frm-search-box">' +
                        '<input type="text" id="frm-category-search" placeholder="Search roles by category...">' +
                        '<select id="frm-category-filter">' +
                        '<option value="">All Categories</option>' +
                        '</select>' +
                        '</div>';
        $('.frm-admin-wrap h1').after(searchHtml);
        
        // Populate category filter
        $('.frm-category-section').each(function() {
            var categoryKey = $(this).data('category');
            var categoryName = $(this).find('.frm-category-title h2').text();
            $('#frm-category-filter').append('<option value="' + categoryKey + '">' + categoryName + '</option>');
        });
        
        // Search functionality
        $('#frm-category-search').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            
            $('.frm-category-section').each(function() {
                var categorySection = $(this);
                var categoryName = categorySection.find('.frm-category-title h2').text().toLowerCase();
                var hasMatchingRoles = false;
                
                categorySection.find('.frm-role-card').each(function() {
                    var roleName = $(this).find('.frm-role-header h3').text().toLowerCase();
                    var roleKey = $(this).data('role').toLowerCase();
                    
                    if (roleName.indexOf(searchTerm) > -1 || roleKey.indexOf(searchTerm) > -1) {
                        $(this).show();
                        hasMatchingRoles = true;
                    } else {
                        $(this).hide();
                    }
                });
                
                if (categoryName.indexOf(searchTerm) > -1 || hasMatchingRoles) {
                    categorySection.show();
                } else {
                    categorySection.hide();
                }
            });
        });
        
        // Category filter
        $('#frm-category-filter').on('change', function() {
            var selectedCategory = $(this).val();
            
            $('.frm-category-section').each(function() {
                var categoryKey = $(this).data('category');
                
                if (selectedCategory === '' || categoryKey === selectedCategory) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }
});

// Add custom styles for search box and stats
var customStyles = '<style>' +
    '.frm-search-box { margin: 20px 0; }' +
    '.frm-search-box input { width: 300px; padding: 8px; font-size: 14px; }' +
    '.frm-stats { background: #f0f0f1; padding: 15px; border-radius: 4px; margin: 20px 0; display: flex; gap: 30px; }' +
    '.frm-stats span { font-size: 14px; }' +
    '.frm-export-import { margin: 20px 0; display: flex; gap: 10px; }' +
    '.frm-role-header.collapsed { background: #e5e5e5; }' +
    '.frm-role-header { cursor: pointer; transition: background 0.3s; }' +
    '.frm-role-header:hover { background: #e8e8e8; }' +
    '</style>';
jQuery('head').append(customStyles);