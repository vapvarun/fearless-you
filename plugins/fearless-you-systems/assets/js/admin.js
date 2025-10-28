/**
 * FYM Admin JavaScript
 */

jQuery(document).ready(function($) {
    // Sync roles function
    window.fymSyncRoles = function() {
        if (confirm('This will sync all user roles. Continue?')) {
            $.ajax({
                url: fym_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'fym_sync_roles',
                    nonce: fym_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Roles synced successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while syncing roles.');
                }
            });
        }
    };

    // Export members function
    window.fymExportMembers = function() {
        window.location.href = fym_admin.ajax_url + '?action=fym_export_members&nonce=' + fym_admin.nonce;
    };

    // Auto-refresh stats every 60 seconds
    if ($('.fym-stats-grid').length) {
        setInterval(function() {
            $.ajax({
                url: fym_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'fym_get_stats',
                    nonce: fym_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateStats(response.data);
                    }
                }
            });
        }, 60000);
    }

    function updateStats(stats) {
        $('.fym-stat-card').each(function(index) {
            var $number = $(this).find('.fym-stat-number');
            if (stats[index]) {
                $number.text(stats[index]);
            }
        });
    }

    // WP Fusion Tag Autocomplete
    $('.fym-wpfusion-tags').each(function() {
        var $container = $(this);
        var fieldName = $container.data('field');
        var $searchInput = $container.find('.fym-tag-search');
        var $suggestions = $container.find('.fym-tag-suggestions');
        var $selectedTags = $container.find('.fym-tags-list');
        var $noTagsMessage = $container.find('.fym-no-tags');

        // Get available tags for this field
        var availableTags = window.fymAvailableTags && window.fymAvailableTags[fieldName] ? window.fymAvailableTags[fieldName] : {};

        // Get already selected tag IDs
        function getSelectedTagIds() {
            var ids = [];
            $container.find('.fym-tag-item').each(function() {
                ids.push($(this).data('tag-id').toString());
            });
            return ids;
        }

        // Search function
        function searchTags(query) {
            query = query.toLowerCase();
            var results = [];
            var selectedIds = getSelectedTagIds();

            for (var tagId in availableTags) {
                var tagName = availableTags[tagId].toLowerCase();

                // Skip already selected tags
                if (selectedIds.indexOf(tagId.toString()) !== -1) {
                    continue;
                }

                // Search in tag name
                if (tagName.indexOf(query) !== -1) {
                    results.push({
                        id: tagId,
                        name: availableTags[tagId],
                        relevance: tagName.indexOf(query) === 0 ? 1 : 0
                    });
                }

                // Limit results to 20
                if (results.length >= 20) {
                    break;
                }
            }

            // Sort by relevance (starts with query first)
            results.sort(function(a, b) {
                return b.relevance - a.relevance;
            });

            return results;
        }

        // Show suggestions
        function showSuggestions(results) {
            $suggestions.empty();

            if (results.length === 0) {
                $suggestions.append('<div class="fym-tag-suggestion" style="color: #999; font-style: italic;">No matching tags found</div>');
            } else {
                results.forEach(function(tag) {
                    var $suggestion = $('<div class="fym-tag-suggestion">')
                        .attr('data-tag-id', tag.id)
                        .attr('data-tag-name', tag.name)
                        .html(tag.name + '<span class="fym-tag-suggestion-id">#' + tag.id + '</span>');

                    $suggestion.on('click', function() {
                        selectTag(tag.id, tag.name);
                    });

                    $suggestions.append($suggestion);
                });
            }

            $suggestions.show();
        }

        // Select a tag
        function selectTag(tagId, tagName) {
            // Hide no tags message
            $noTagsMessage.hide();

            // Create tag element
            var $tag = $('<span class="fym-tag-item">')
                .attr('data-tag-id', tagId)
                .html(tagName + '<button type="button" class="fym-remove-tag" aria-label="Remove tag">×</button>');

            // Add hidden input
            $tag.append('<input type="hidden" name="fym_options[' + fieldName + '][]" value="' + tagId + '">');

            // Add remove functionality
            $tag.find('.fym-remove-tag').on('click', function() {
                $tag.remove();
                if ($selectedTags.children('.fym-tag-item').length === 0) {
                    $noTagsMessage.show();
                }
            });

            // Add to selected tags
            $selectedTags.append($tag);

            // Clear search and hide suggestions
            $searchInput.val('');
            $suggestions.hide();
        }

        // Search input handler
        var searchTimeout;
        $searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            var query = $(this).val().trim();

            if (query.length < 2) {
                $suggestions.hide();
                return;
            }

            searchTimeout = setTimeout(function() {
                var results = searchTags(query);
                showSuggestions(results);
            }, 300);
        });

        // Focus/blur handlers
        $searchInput.on('focus', function() {
            var query = $(this).val().trim();
            if (query.length >= 2) {
                var results = searchTags(query);
                showSuggestions(results);
            }
        });

        // Hide suggestions when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.fym-tag-input-wrapper').length) {
                $suggestions.hide();
            }
        });

        // Handle existing remove buttons
        $container.find('.fym-remove-tag').on('click', function() {
            $(this).parent('.fym-tag-item').remove();
            if ($selectedTags.children('.fym-tag-item').length === 0) {
                $noTagsMessage.show();
            }
        });

        // Keyboard navigation
        var selectedIndex = -1;

        $searchInput.on('keydown', function(e) {
            var $visibleSuggestions = $suggestions.find('.fym-tag-suggestion:not([style*="color: #999"])');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, $visibleSuggestions.length - 1);
                updateSelection();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0) {
                    $visibleSuggestions.eq(selectedIndex).click();
                    selectedIndex = -1;
                }
            } else if (e.key === 'Escape') {
                $suggestions.hide();
                selectedIndex = -1;
            }
        });

        function updateSelection() {
            $suggestions.find('.fym-tag-suggestion').removeClass('selected');
            if (selectedIndex >= 0) {
                $suggestions.find('.fym-tag-suggestion:not([style*="color: #999"])').eq(selectedIndex).addClass('selected');
            }
        }
    });
});