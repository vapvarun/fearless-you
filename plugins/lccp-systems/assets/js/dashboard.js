/**
 * Dashboard JavaScript for Dasher
 *
 * @package Dasher
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Initialize Dasher dashboard functionality
     */
    function initDasherDashboard() {
        // Initialize tabs if they exist
        if ($.fn.tabs && $('#dasher-dashboard-tabs').length) {
            $('#dasher-dashboard-tabs').tabs();
        }
        
        // Set up student card toggling
        initCardToggle();
        
        // Set up search filtering
        initSearchFilter();
        
        // Set up progress filtering
        initProgressFilter();
        
        // Set up student detail modal
        initStudentModal();
    }
    
    /**
     * Initialize card toggle functionality
     */
    function initCardToggle() {
        $('.dasher-card-toggle').on('click', function() {
            var $card = $(this).closest('.dasher-student-card');
            $card.find('.dasher-student-card-content').slideToggle(200);
            $(this).find('.dashicons').toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
        });
    }
    
    /**
     * Initialize search filter functionality
     */
    function initSearchFilter() {
        $('#dasher-student-search').on('keyup', function() {
            var searchText = $(this).val().toLowerCase();
            
            $('.dasher-student-card').each(function() {
                var studentName = $(this).find('.dasher-student-name').text().toLowerCase();
                var studentEmail = $(this).find('.dasher-student-details p:first-child').text().toLowerCase();
                
                if (studentName.indexOf(searchText) > -1 || studentEmail.indexOf(searchText) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }
    
    /**
     * Initialize progress filter functionality
     */
    function initProgressFilter() {
        $('#dasher-progress-filter').on('change', function() {
            var filter = $(this).val();
            
            if (filter === 'all') {
                $('.dasher-student-card').show();
            } else {
                $('.dasher-student-card').hide();
                $('.dasher-student-card.' + filter).show();
            }
        });
    }
    
    /**
     * Initialize student detail modal
     */
    function initStudentModal() {
        // Open modal
        $('.dasher-view-detailed-progress').on('click', function(e) {
            e.preventDefault();
            
            var studentId = $(this).data('student-id');
            openStudentModal(studentId);
        });
        
        // Close modal
        $('.dasher-modal-close, #dasher-modal-bg').on('click', function() {
            closeStudentModal();
        });
        
        // Close on escape key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27 && $('#dasher-student-modal').is(':visible')) {
                closeStudentModal();
            }
        });
    }
    
    /**
     * Open student detail modal and load data
     *
     * @param {number} studentId The student ID to load data for
     */
    function openStudentModal(studentId) {
        // Show modal
        $('#dasher-modal-bg').show();
        $('#dasher-student-modal').show();
        $('#dasher-modal-loading').show();
        $('#dasher-modal-content').empty();
        
        // Load student data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dasher_get_student_progress',
                nonce: dasher_data.nonce,
                student_id: studentId
            },
            success: function(response) {
                $('#dasher-modal-loading').hide();
                
                if (response.success) {
                    renderStudentDetails(response.data.progress);
                } else {
                    $('#dasher-modal-content').html('<p class="dasher-error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#dasher-modal-loading').hide();
                $('#dasher-modal-content').html('<p class="dasher-error">Error loading student data. Please try again.</p>');
            }
        });
    }
    
    /**
     * Close student detail modal
     */
    function closeStudentModal() {
        $('#dasher-modal-bg').hide();
        $('#dasher-student-modal').hide();
    }
    
    /**
     * Render student details in the modal
     *
     * @param {Object} progress Student progress data
     */
    function renderStudentDetails(progress) {
        var student = progress.student;
        var courses = progress.courses;
        
        // Set modal title
        $('#dasher-modal-title').text(student.name + ' - Progress Details');
        
        // Build detailed content
        var content = '<div class="dasher-detailed-progress">';
        
        // Student info
        content += '<div class="dasher-detailed-student-info">';
        content += '<img src="' + student.avatar + '" alt="' + student.name + '" class="dasher-student-avatar">';
        content += '<div class="dasher-student-meta">';
        content += '<h3>' + student.name + '</h3>';
        content += '<p>' + student.email + '</p>';
        content += '</div>';
        content += '</div>';
        
        // Current progress
        content += '<div class="dasher-detailed-current-progress">';
        content += '<p><strong>Current Progress:</strong> ' + progress.current_progress + '</p>';
        content += '<p><strong>Coming Up:</strong> ' + progress.coming_up + '</p>';
        
        if (progress.big_bird) {
            content += '<p><strong>BigBird:</strong> ' + progress.big_bird.name + '</p>';
        } else {
            content += '<p><strong>BigBird:</strong> <span class="dasher-unassigned">Unassigned</span></p>';
        }
        content += '</div>';
        
        // Course details
        content += '<div class="dasher-detailed-courses">';
        content += '<h3>Course Details</h3>';
        
        if (courses.length === 0) {
            content += '<p>No courses enrolled</p>';
        } else {
            content += '<div class="dasher-detailed-course-list">';
            
            for (var i = 0; i < courses.length; i++) {
                var course = courses[i];
                
                content += '<div class="dasher-detailed-course">';
                content += '<h4>' + course.title + '</h4>';
                
                content += '<div class="dasher-progress-bar-container">';
                content += '<div class="dasher-progress-bar" style="width: ' + course.percentage + '%;"></div>';
                content += '</div>';
                
                content += '<div class="dasher-progress-stats">';
                content += '<span class="dasher-progress-percentage">' + course.percentage + '%</span>';
                content += '<span class="dasher-progress-steps">' + course.completed_steps + ' / ' + course.total_steps + ' steps completed</span>';
                content += '</div>';
                
                if (course.next_lesson) {
                    content += '<div class="dasher-next-lesson">';
                    content += '<p><strong>Next Module:</strong> ' + course.next_lesson.title + '</p>';
                    content += '</div>';
                }
                
                content += '</div>';
            }
            
            content += '</div>';
        }
        
        content += '</div>';
        content += '</div>';
        
        $('#dasher-modal-content').html(content);
    }
    
    /**
     * Initialize Dasher assignment form
     */
    function initDasherAssignmentForm() {
        $('#dasher-assign-bigbird-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $spinner = $form.find('.spinner');
            var $message = $('#dasher-assignment-message');
            var studentId = $form.find('input[name="student_id"]').val();
            var bigbirdId = $form.find('input[name="bigbird_id"]:checked').val();
            
            if (!bigbirdId) {
                $message.html('Please select a BigBird to assign').addClass('error').removeClass('success').show();
                return;
            }
            
            $spinner.addClass('is-active');
            $message.hide();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dasher_assign_bigbird',
                    nonce: dasher_data.nonce,
                    student_id: studentId,
                    bigbird_id: bigbirdId
                },
                success: function(response) {
                    $spinner.removeClass('is-active');
                    
                    if (response.success) {
                        $message.html(response.data.message).addClass('success').removeClass('error').show();
                        
                        // Update UI to show the assignment
                        if (!$('.dasher-current-assignment').length) {
                            var $selected = $('input[name="bigbird_id"]:checked');
                            var bigbirdName = $selected.closest('.dasher-bigbird-option').find('h4').text();
                            
                            $form.before('<div class="dasher-current-assignment"><p>' + 
                                'Currently assigned to: <strong>' + 
                                bigbirdName + '</strong></p></div>');
                        } else {
                            var $selected = $('input[name="bigbird_id"]:checked');
                            var bigbirdName = $selected.closest('.dasher-bigbird-option').find('h4').text();
                            
                            $('.dasher-current-assignment').html('<p>' + 
                                'Currently assigned to: <strong>' + 
                                bigbirdName + '</strong></p>');
                        }
                    } else {
                        $message.html(response.data.message).addClass('error').removeClass('success').show();
                    }
                },
                error: function() {
                    $spinner.removeClass('is-active');
                    $message.html('An error occurred. Please try again.').addClass('error').removeClass('success').show();
                }
            });
        });
    }
    
    // Initialize everything when document is ready
    $(document).ready(function() {
        initDasherDashboard();
        initDasherAssignmentForm();
        
        // Refresh data button
        $('#dasher-refresh-data').on('click', function() {
            location.reload();
        });
    });
    
})(jQuery); 