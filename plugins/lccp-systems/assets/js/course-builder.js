jQuery(document).ready(function($) {
    var selectedCourse = null;
    var selectedLesson = null;
    var hasChanges = false;

    // Initialize sortable for lessons
    $('.sortable-lessons').sortable({
        handle: '.drag-handle',
        placeholder: 'sortable-placeholder',
        connectWith: '.sortable-lessons',
        update: function(event, ui) {
            hasChanges = true;
            updateCourseHighlight();
        }
    });

    // Initialize sortable for topics
    $('.sortable-topics').sortable({
        handle: '.drag-handle',
        placeholder: 'sortable-placeholder',
        connectWith: '.sortable-topics',
        update: function(event, ui) {
            hasChanges = true;
        }
    });

    // Course selection
    $('.dasher-course-box').on('click', function(e) {
        if (!$(e.target).hasClass('dashicons-edit')) {
            $('.dasher-course-box').removeClass('active');
            $(this).addClass('active');
            selectedCourse = $(this).data('course-id');
            updateAddToCourseButton();
        }
    });

    // Expand/collapse topics
    $(document).on('click', '.expand-topics', function() {
        $(this).toggleClass('expanded');
        $(this).closest('.dasher-lesson-item').find('.dasher-lesson-topics').slideToggle(200);
    });

    // Tab switching
    $('.dasher-tab').on('click', function() {
        $('.dasher-tab').removeClass('active');
        $(this).addClass('active');
        
        var tab = $(this).data('tab');
        $('.dasher-pool').removeClass('active');
        $('#' + tab + '-pool').addClass('active');
    });

    // Search functionality
    $('.dasher-search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        var $pool = $(this).closest('.dasher-pool');
        
        $pool.find('.dasher-pool-item').each(function() {
            var title = $(this).find('.item-title').text().toLowerCase();
            if (title.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Item selection in pools
    $(document).on('change', '.item-checkbox', function() {
        $(this).closest('.dasher-pool-item').toggleClass('selected');
        updateAddToCourseButton();
    });

    // Add to course button
    $('.add-to-course-btn').on('click', function() {
        if (!selectedCourse) {
            alert('Please select a course first');
            return;
        }

        var selectedLessons = [];
        $('.lesson-pool-item.selected').each(function() {
            selectedLessons.push($(this).data('lesson-id'));
        });

        if (selectedLessons.length === 0) {
            alert('Please select at least one lesson');
            return;
        }

        // Add lessons to course visually
        var $courseBox = $('.dasher-course-box[data-course-id="' + selectedCourse + '"]');
        var $lessonContainer = $courseBox.find('.sortable-lessons');
        
        selectedLessons.forEach(function(lessonId) {
            var $lessonItem = $('.lesson-pool-item[data-lesson-id="' + lessonId + '"]');
            var lessonTitle = $lessonItem.find('.item-title').text();
            
            var newLessonHtml = '<div class="dasher-lesson-item" data-lesson-id="' + lessonId + '">' +
                '<div class="lesson-header">' +
                '<span class="dashicons dashicons-menu drag-handle"></span>' +
                '<span class="dashicons dashicons-media-text"></span>' +
                '<span class="lesson-title">' + lessonTitle + '</span>' +
                '<span class="lesson-actions">' +
                '<span class="dashicons dashicons-arrow-down expand-topics" title="Show Topics"></span>' +
                '<span class="dashicons dashicons-trash remove-lesson" title="Remove from Course"></span>' +
                '</span>' +
                '</div>' +
                '<div class="dasher-lesson-topics sortable-topics" style="display: none;" data-lesson-id="' + lessonId + '">' +
                '<p class="no-topics">No topics in this lesson</p>' +
                '</div>' +
                '</div>';
            
            $lessonContainer.find('.no-lessons').remove();
            $lessonContainer.append(newLessonHtml);
            
            // Remove from unassigned pool
            $lessonItem.fadeOut(300, function() {
                $(this).remove();
            });
        });

        // Re-initialize sortable for new topics containers
        $('.sortable-topics').sortable({
            handle: '.drag-handle',
            placeholder: 'sortable-placeholder',
            connectWith: '.sortable-topics',
            update: function(event, ui) {
                hasChanges = true;
            }
        });

        hasChanges = true;
        updateAddToCourseButton();
    });

    // Remove lesson from course
    $(document).on('click', '.remove-lesson', function() {
        if (confirm('Remove this lesson from the course?')) {
            var $lessonItem = $(this).closest('.dasher-lesson-item');
            var lessonId = $lessonItem.data('lesson-id');
            var lessonTitle = $lessonItem.find('.lesson-title').text();
            
            // Add back to unassigned pool
            var poolItemHtml = '<div class="dasher-pool-item lesson-pool-item" data-lesson-id="' + lessonId + '">' +
                '<input type="checkbox" class="item-checkbox">' +
                '<span class="dashicons dashicons-media-text"></span>' +
                '<span class="item-title">' + lessonTitle + '</span>' +
                '<a href="#" target="_blank" class="dashicons dashicons-edit" title="Edit Lesson"></a>' +
                '</div>';
            
            $('#lessons-pool .dasher-items-list').append(poolItemHtml);
            $('#lessons-pool .no-items').remove();
            
            // Remove from course
            $lessonItem.fadeOut(300, function() {
                $(this).remove();
                
                // Check if course is now empty
                var $courseBox = $('.dasher-course-box[data-course-id="' + selectedCourse + '"]');
                if ($courseBox.find('.dasher-lesson-item').length === 0) {
                    $courseBox.find('.sortable-lessons').html('<p class="no-lessons">No lessons in this course yet. Drag lessons here or use "Add to Course" button.</p>');
                }
            });
            
            hasChanges = true;
        }
    });

    // Remove topic from lesson
    $(document).on('click', '.remove-topic', function() {
        if (confirm('Remove this topic from the lesson?')) {
            var $topicItem = $(this).closest('.dasher-topic-item');
            $topicItem.fadeOut(300, function() {
                $(this).remove();
            });
            hasChanges = true;
        }
    });

    // Update add to course button state
    function updateAddToCourseButton() {
        var hasSelectedItems = $('.lesson-pool-item.selected').length > 0;
        var hasCourseSelected = selectedCourse !== null;
        
        $('.add-to-course-btn').prop('disabled', !(hasSelectedItems && hasCourseSelected));
    }

    // Update course highlighting
    function updateCourseHighlight() {
        $('.dasher-course-box').each(function() {
            if ($(this).find('.dasher-lesson-item').length > 0) {
                $(this).css('opacity', '1');
            } else {
                $(this).css('opacity', '0.8');
            }
        });
    }

    // Save course structure
    $('#save-course-structure').on('click', function() {
        if (!hasChanges) {
            alert('No changes to save');
            return;
        }

        var $button = $(this);
        var $spinner = $('.spinner');
        var $message = $('.save-message');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $message.html('');

        // Gather course structure data
        var courseStructure = {};
        
        $('.dasher-course-box').each(function() {
            var courseId = $(this).data('course-id');
            var lessons = [];
            
            $(this).find('.dasher-lesson-item').each(function(index) {
                var lessonId = $(this).data('lesson-id');
                var topics = [];
                
                $(this).find('.dasher-topic-item').each(function(topicIndex) {
                    topics.push({
                        id: $(this).data('topic-id'),
                        order: topicIndex
                    });
                });
                
                lessons.push({
                    id: lessonId,
                    order: index,
                    topics: topics
                });
            });
            
            courseStructure[courseId] = lessons;
        });

        // Send AJAX request to save
        $.ajax({
            url: ajaxurl || dasher_data.ajax_url,
            type: 'POST',
            data: {
                action: 'dasher_save_course_structure',
                nonce: dasher_data.nonce,
                structure: JSON.stringify(courseStructure)
            },
            success: function(response) {
                $spinner.removeClass('is-active');
                $button.prop('disabled', false);
                
                if (response.success) {
                    $message.html('<span style="color: green;">' + response.data.message + '</span>');
                    hasChanges = false;
                    
                    // Fade out message after 3 seconds
                    setTimeout(function() {
                        $message.fadeOut();
                    }, 3000);
                } else {
                    $message.html('<span style="color: red;">Error: ' + response.data.message + '</span>');
                }
            },
            error: function() {
                $spinner.removeClass('is-active');
                $button.prop('disabled', false);
                $message.html('<span style="color: red;">Error saving course structure</span>');
            }
        });
    });

    // Warn before leaving if there are unsaved changes
    $(window).on('beforeunload', function() {
        if (hasChanges) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
});