<?php
/**
 * LCCP Enhanced Audio Review System with Timecode Marking
 * Allows mentors to mark specific timecodes with notes for PC review
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Audio_Review_Enhanced {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize database tables
        add_action('init', array($this, 'create_tables'));

        // Shortcodes
        add_shortcode('lccp_audio_review_player', array($this, 'render_audio_review_player'));
        add_shortcode('lccp_student_feedback_notes', array($this, 'render_student_notes'));

        // AJAX handlers
        add_action('wp_ajax_lccp_add_timecode_marker', array($this, 'ajax_add_timecode_marker'));
        add_action('wp_ajax_lccp_get_timecode_markers', array($this, 'ajax_get_timecode_markers'));
        add_action('wp_ajax_lccp_delete_marker', array($this, 'ajax_delete_marker'));
        add_action('wp_ajax_lccp_export_notes_pdf', array($this, 'ajax_export_notes_pdf'));
        add_action('wp_ajax_lccp_save_audio_review', array($this, 'ajax_save_audio_review'));

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table for timecode markers
        $markers_table = $wpdb->prefix . 'lccp_audio_markers';

        $sql = "CREATE TABLE IF NOT EXISTS $markers_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) NOT NULL,
            reviewer_id bigint(20) NOT NULL,
            reviewer_role varchar(50),
            timecode decimal(10,2) NOT NULL,
            note text NOT NULL,
            category varchar(50),
            severity varchar(20) DEFAULT 'info',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY reviewer_id (reviewer_id),
            KEY timecode (timecode)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_assets() {
        wp_enqueue_style('lccp-audio-review',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/audio-review.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script('lccp-audio-review',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/audio-review.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('lccp-audio-review', 'lccp_audio_review', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lccp_audio_review'),
            'user_role' => $this->get_user_role()
        ));
    }

    public function render_audio_review_player($atts) {
        $atts = shortcode_atts(array(
            'submission_id' => 0,
            'audio_url' => '',
            'mode' => 'review' // 'review' or 'view'
        ), $atts);

        if (!$atts['submission_id'] || !$atts['audio_url']) {
            return '<p>Invalid audio submission.</p>';
        }

        $user_id = get_current_user_id();
        $user_role = $this->get_user_role();
        $can_add_markers = in_array($user_role, array('mentor', 'bigbird', 'pc', 'administrator'));

        ob_start();
        ?>
        <div class="lccp-audio-review-container" data-submission-id="<?php echo esc_attr($atts['submission_id']); ?>">
            <!-- Enhanced Audio Player with Waveform -->
            <div class="lccp-audio-player-wrapper">
                <div class="lccp-waveform-container">
                    <canvas id="waveform-<?php echo $atts['submission_id']; ?>" class="lccp-waveform"></canvas>
                    <div class="lccp-timeline-markers" id="markers-<?php echo $atts['submission_id']; ?>"></div>
                    <div class="lccp-playhead"></div>
                </div>

                <audio id="audio-<?php echo $atts['submission_id']; ?>"
                       class="lccp-audio-element"
                       src="<?php echo esc_url($atts['audio_url']); ?>">
                </audio>

                <div class="lccp-audio-controls">
                    <button class="lccp-play-pause" data-playing="false">
                        <span class="play-icon">▶</span>
                        <span class="pause-icon" style="display:none;">⏸</span>
                    </button>

                    <div class="lccp-time-display">
                        <span class="current-time">0:00</span> /
                        <span class="duration">0:00</span>
                    </div>

                    <div class="lccp-playback-speed">
                        <select class="speed-control">
                            <option value="0.5">0.5x</option>
                            <option value="0.75">0.75x</option>
                            <option value="1" selected>1x</option>
                            <option value="1.25">1.25x</option>
                            <option value="1.5">1.5x</option>
                            <option value="2">2x</option>
                        </select>
                    </div>

                    <?php if ($can_add_markers): ?>
                    <button class="lccp-add-marker-btn" title="Add marker at current time">
                        📍 Add Marker
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Marker Input Panel (for reviewers) -->
            <?php if ($can_add_markers): ?>
            <div class="lccp-marker-input-panel" style="display:none;">
                <h4>Add Note at <span class="marker-timecode">0:00</span></h4>

                <div class="lccp-marker-form">
                    <div class="form-group">
                        <label>Category:</label>
                        <select class="marker-category">
                            <option value="coaching_technique">Coaching Technique</option>
                            <option value="communication">Communication</option>
                            <option value="professionalism">Professionalism</option>
                            <option value="feedback">Client Feedback</option>
                            <option value="improvement">Area for Improvement</option>
                            <option value="excellent">Excellent Work</option>
                            <option value="question">Question/Clarification</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Severity/Type:</label>
                        <select class="marker-severity">
                            <option value="praise">✅ Praise</option>
                            <option value="info">ℹ️ Information</option>
                            <option value="suggestion">💡 Suggestion</option>
                            <option value="warning">⚠️ Warning</option>
                            <option value="critical">❌ Critical</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Note:</label>
                        <textarea class="marker-note" rows="3" placeholder="Enter your feedback..."></textarea>
                    </div>

                    <div class="form-actions">
                        <button class="save-marker-btn">Save Marker</button>
                        <button class="cancel-marker-btn">Cancel</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Markers List Panel -->
            <div class="lccp-markers-list">
                <h3>Review Notes & Feedback</h3>
                <div class="lccp-markers-filter">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="mentor">Mentor</button>
                    <button class="filter-btn" data-filter="pc">PC</button>
                    <button class="filter-btn" data-filter="praise">Praise</button>
                    <button class="filter-btn" data-filter="critical">Issues</button>
                </div>

                <div class="lccp-markers-timeline">
                    <!-- Markers will be loaded here via AJAX -->
                </div>
            </div>

            <!-- PC Review Panel (only visible to PC) -->
            <?php if ($user_role === 'pc' || $user_role === 'administrator'): ?>
            <div class="lccp-pc-review-panel">
                <h3>Program Coordinator Review</h3>
                <div class="lccp-pc-notes">
                    <textarea class="pc-final-notes" rows="5"
                              placeholder="Add final review notes visible to both mentor and student..."></textarea>
                    <button class="save-pc-review">Save PC Review</button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Student View Panel -->
            <?php if ($atts['mode'] === 'view'): ?>
            <div class="lccp-student-actions">
                <button class="lccp-export-pdf" data-submission-id="<?php echo $atts['submission_id']; ?>">
                    📄 Export Notes as PDF
                </button>
                <button class="lccp-print-notes">
                    🖨️ Print Notes
                </button>
            </div>
            <?php endif; ?>
        </div>

        <style>
        .lccp-audio-review-container {
            max-width: 1200px;
            margin: 20px 0;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .lccp-waveform-container {
            position: relative;
            height: 150px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            cursor: pointer;
        }

        .lccp-waveform {
            width: 100%;
            height: 100%;
        }

        .lccp-timeline-markers {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }

        .lccp-marker-pin {
            position: absolute;
            width: 2px;
            height: 100%;
            cursor: pointer;
            pointer-events: all;
        }

        .lccp-marker-pin.praise { background: #4CAF50; }
        .lccp-marker-pin.info { background: #2196F3; }
        .lccp-marker-pin.suggestion { background: #FFC107; }
        .lccp-marker-pin.warning { background: #FF9800; }
        .lccp-marker-pin.critical { background: #f44336; }

        .lccp-marker-pin:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
        }

        .lccp-playhead {
            position: absolute;
            width: 2px;
            height: 100%;
            background: #ff0000;
            pointer-events: none;
            left: 0;
        }

        .lccp-audio-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }

        .lccp-play-pause {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2271b1;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .lccp-play-pause:hover {
            background: #135e96;
        }

        .lccp-time-display {
            font-family: monospace;
            font-size: 14px;
        }

        .lccp-add-marker-btn {
            margin-left: auto;
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .lccp-add-marker-btn:hover {
            background: #45a049;
        }

        .lccp-marker-input-panel {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
            border: 2px solid #2271b1;
        }

        .lccp-marker-form .form-group {
            margin-bottom: 15px;
        }

        .lccp-marker-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .lccp-marker-form select,
        .lccp-marker-form textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .lccp-marker-form .form-actions {
            display: flex;
            gap: 10px;
        }

        .save-marker-btn,
        .cancel-marker-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-marker-btn {
            background: #4CAF50;
            color: white;
        }

        .cancel-marker-btn {
            background: #ccc;
            color: #333;
        }

        .lccp-markers-list {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 4px;
        }

        .lccp-markers-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-btn {
            padding: 6px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }

        .filter-btn.active {
            background: #2271b1;
            color: white;
            border-color: #2271b1;
        }

        .lccp-marker-item {
            background: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 10px;
            border-left: 4px solid #ddd;
            cursor: pointer;
            transition: all 0.3s;
        }

        .lccp-marker-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }

        .lccp-marker-item.praise { border-left-color: #4CAF50; }
        .lccp-marker-item.info { border-left-color: #2196F3; }
        .lccp-marker-item.suggestion { border-left-color: #FFC107; }
        .lccp-marker-item.warning { border-left-color: #FF9800; }
        .lccp-marker-item.critical { border-left-color: #f44336; }

        .lccp-marker-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .lccp-marker-time {
            font-family: monospace;
            font-weight: bold;
            color: #2271b1;
            cursor: pointer;
        }

        .lccp-marker-time:hover {
            text-decoration: underline;
        }

        .lccp-marker-reviewer {
            font-size: 12px;
            color: #666;
        }

        .lccp-marker-category {
            display: inline-block;
            padding: 2px 8px;
            background: #e0e0e0;
            border-radius: 3px;
            font-size: 11px;
            margin-right: 5px;
        }

        .lccp-marker-note {
            color: #333;
            line-height: 1.5;
        }

        .lccp-pc-review-panel {
            margin-top: 30px;
            padding: 20px;
            background: #fff8dc;
            border: 2px solid #ffd700;
            border-radius: 4px;
        }

        .lccp-pc-notes textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .save-pc-review {
            padding: 10px 20px;
            background: #ffd700;
            color: #333;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .save-pc-review:hover {
            background: #ffed4e;
        }

        .lccp-student-actions {
            margin-top: 30px;
            padding: 20px;
            background: #f0f8ff;
            border-radius: 4px;
            display: flex;
            gap: 15px;
        }

        .lccp-export-pdf,
        .lccp-print-notes {
            padding: 10px 20px;
            background: #2271b1;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .lccp-export-pdf:hover,
        .lccp-print-notes:hover {
            background: #135e96;
        }

        /* Popup for PC when hovering over markers */
        .lccp-marker-popup {
            position: absolute;
            background: white;
            border: 2px solid #2271b1;
            border-radius: 4px;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            max-width: 300px;
        }

        .lccp-marker-popup h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
        }

        .lccp-marker-popup p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }

        @media print {
            .lccp-audio-player-wrapper,
            .lccp-audio-controls,
            .lccp-marker-input-panel,
            .lccp-pc-review-panel,
            .lccp-student-actions {
                display: none;
            }

            .lccp-markers-list {
                page-break-inside: avoid;
            }

            .lccp-marker-item {
                page-break-inside: avoid;
                border: 1px solid #ddd;
                margin-bottom: 15px;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            const submissionId = <?php echo $atts['submission_id']; ?>;
            const audio = document.getElementById('audio-' + submissionId);
            const playBtn = $('.lccp-play-pause');
            const playIcon = playBtn.find('.play-icon');
            const pauseIcon = playBtn.find('.pause-icon');
            const currentTimeSpan = $('.current-time');
            const durationSpan = $('.duration');
            const playhead = $('.lccp-playhead');
            const waveformContainer = $('.lccp-waveform-container');
            const speedControl = $('.speed-control');
            const addMarkerBtn = $('.lccp-add-marker-btn');
            const markerPanel = $('.lccp-marker-input-panel');
            const markersTimeline = $('#markers-' + submissionId);

            // Initialize audio player
            audio.addEventListener('loadedmetadata', function() {
                durationSpan.text(formatTime(audio.duration));
            });

            // Play/pause functionality
            playBtn.on('click', function() {
                if (audio.paused) {
                    audio.play();
                    playIcon.hide();
                    pauseIcon.show();
                    $(this).attr('data-playing', 'true');
                } else {
                    audio.pause();
                    playIcon.show();
                    pauseIcon.hide();
                    $(this).attr('data-playing', 'false');
                }
            });

            // Update time display
            audio.addEventListener('timeupdate', function() {
                currentTimeSpan.text(formatTime(audio.currentTime));
                const progress = (audio.currentTime / audio.duration) * 100;
                playhead.css('left', progress + '%');

                // Check for markers at current time
                checkMarkersAtTime(audio.currentTime);
            });

            // Click on waveform to seek
            waveformContainer.on('click', function(e) {
                if ($(e.target).hasClass('lccp-marker-pin')) return;
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const clickedTime = (x / rect.width) * audio.duration;
                audio.currentTime = clickedTime;
            });

            // Playback speed control
            speedControl.on('change', function() {
                audio.playbackRate = parseFloat($(this).val());
            });

            // Add marker functionality
            addMarkerBtn.on('click', function() {
                const currentTime = audio.currentTime;
                markerPanel.find('.marker-timecode').text(formatTime(currentTime));
                markerPanel.data('timecode', currentTime);
                markerPanel.slideDown();
                audio.pause();
                playIcon.show();
                pauseIcon.hide();
            });

            // Save marker
            $('.save-marker-btn').on('click', function() {
                const timecode = markerPanel.data('timecode');
                const category = $('.marker-category').val();
                const severity = $('.marker-severity').val();
                const note = $('.marker-note').val();

                if (!note.trim()) {
                    alert('Please enter a note');
                    return;
                }

                $.ajax({
                    url: lccp_audio_review.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'lccp_add_timecode_marker',
                        nonce: lccp_audio_review.nonce,
                        submission_id: submissionId,
                        timecode: timecode,
                        category: category,
                        severity: severity,
                        note: note
                    },
                    success: function(response) {
                        if (response.success) {
                            loadMarkers();
                            markerPanel.slideUp();
                            $('.marker-note').val('');
                            showNotification('Marker added successfully');
                        }
                    }
                });
            });

            // Cancel marker
            $('.cancel-marker-btn').on('click', function() {
                markerPanel.slideUp();
                $('.marker-note').val('');
            });

            // Load markers
            function loadMarkers() {
                $.ajax({
                    url: lccp_audio_review.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'lccp_get_timecode_markers',
                        nonce: lccp_audio_review.nonce,
                        submission_id: submissionId
                    },
                    success: function(response) {
                        if (response.success) {
                            displayMarkers(response.data.markers);
                            displayMarkersList(response.data.markers);
                        }
                    }
                });
            }

            // Display markers on timeline
            function displayMarkers(markers) {
                markersTimeline.empty();
                markers.forEach(function(marker) {
                    const position = (marker.timecode / audio.duration) * 100;
                    const pin = $('<div>')
                        .addClass('lccp-marker-pin')
                        .addClass(marker.severity)
                        .css('left', position + '%')
                        .attr('data-tooltip', marker.category + ': ' + marker.note.substring(0, 50) + '...')
                        .attr('data-marker-id', marker.id)
                        .attr('data-timecode', marker.timecode);

                    markersTimeline.append(pin);
                });

                // Click on marker to jump to time
                $('.lccp-marker-pin').on('click', function(e) {
                    e.stopPropagation();
                    const timecode = parseFloat($(this).attr('data-timecode'));
                    audio.currentTime = timecode;
                });
            }

            // Display markers list
            function displayMarkersList(markers) {
                const listContainer = $('.lccp-markers-timeline');
                listContainer.empty();

                markers.forEach(function(marker) {
                    const item = $('<div>')
                        .addClass('lccp-marker-item')
                        .addClass(marker.severity)
                        .html(`
                            <div class="lccp-marker-header">
                                <span class="lccp-marker-time" data-timecode="${marker.timecode}">
                                    ${formatTime(marker.timecode)}
                                </span>
                                <span class="lccp-marker-reviewer">
                                    ${marker.reviewer_name} (${marker.reviewer_role})
                                </span>
                            </div>
                            <div>
                                <span class="lccp-marker-category">${marker.category}</span>
                                <span class="lccp-marker-category">${marker.severity}</span>
                            </div>
                            <div class="lccp-marker-note">${marker.note}</div>
                        `);

                    listContainer.append(item);
                });

                // Click on time to jump
                $('.lccp-marker-time').on('click', function() {
                    const timecode = parseFloat($(this).attr('data-timecode'));
                    audio.currentTime = timecode;
                    audio.play();
                });
            }

            // Filter markers
            $('.filter-btn').on('click', function() {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                const filter = $(this).data('filter');

                if (filter === 'all') {
                    $('.lccp-marker-item').show();
                } else {
                    $('.lccp-marker-item').hide();
                    $('.lccp-marker-item.' + filter).show();
                }
            });

            // Export to PDF
            $('.lccp-export-pdf').on('click', function() {
                $.ajax({
                    url: lccp_audio_review.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'lccp_export_notes_pdf',
                        nonce: lccp_audio_review.nonce,
                        submission_id: submissionId
                    },
                    success: function(response) {
                        if (response.success) {
                            window.open(response.data.pdf_url, '_blank');
                        }
                    }
                });
            });

            // Print notes
            $('.lccp-print-notes').on('click', function() {
                window.print();
            });

            // PC Review save
            $('.save-pc-review').on('click', function() {
                const notes = $('.pc-final-notes').val();

                $.ajax({
                    url: lccp_audio_review.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'lccp_save_audio_review',
                        nonce: lccp_audio_review.nonce,
                        submission_id: submissionId,
                        pc_notes: notes
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('PC review saved successfully');
                        }
                    }
                });
            });

            // Check markers at current time (for popup notifications)
            function checkMarkersAtTime(currentTime) {
                if (lccp_audio_review.user_role === 'pc' || lccp_audio_review.user_role === 'administrator') {
                    $('.lccp-marker-pin').each(function() {
                        const markerTime = parseFloat($(this).attr('data-timecode'));
                        if (Math.abs(currentTime - markerTime) < 0.5) {
                            showMarkerPopup($(this));
                        }
                    });
                }
            }

            // Show marker popup for PC
            function showMarkerPopup($marker) {
                const markerId = $marker.attr('data-marker-id');
                if ($('#popup-' + markerId).length) return;

                const popup = $('<div>')
                    .attr('id', 'popup-' + markerId)
                    .addClass('lccp-marker-popup')
                    .css({
                        top: $marker.offset().top - 100,
                        left: $marker.offset().left - 150
                    });

                // Get marker details via AJAX
                $.ajax({
                    url: lccp_audio_review.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'lccp_get_marker_details',
                        nonce: lccp_audio_review.nonce,
                        marker_id: markerId
                    },
                    success: function(response) {
                        if (response.success) {
                            popup.html(`
                                <h4>${response.data.category}</h4>
                                <p><strong>${response.data.reviewer_name}:</strong></p>
                                <p>${response.data.note}</p>
                            `);
                            $('body').append(popup);

                            setTimeout(function() {
                                popup.fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 5000);
                        }
                    }
                });
            }

            // Helper functions
            function formatTime(seconds) {
                const minutes = Math.floor(seconds / 60);
                const secs = Math.floor(seconds % 60);
                return minutes + ':' + (secs < 10 ? '0' : '') + secs;
            }

            function showNotification(message) {
                const notification = $('<div>')
                    .addClass('lccp-notification')
                    .text(message)
                    .css({
                        position: 'fixed',
                        top: '20px',
                        right: '20px',
                        background: '#4CAF50',
                        color: 'white',
                        padding: '10px 20px',
                        borderRadius: '4px',
                        zIndex: 9999
                    });

                $('body').append(notification);

                setTimeout(function() {
                    notification.fadeOut(function() {
                        $(this).remove();
                    });
                }, 3000);
            }

            // Initial load
            loadMarkers();
        });
        </script>
        <?php

        return ob_get_clean();
    }

    private function get_user_role() {
        $user = wp_get_current_user();
        if (in_array('administrator', $user->roles)) return 'administrator';
        if (in_array('lccp_program_coordinator', $user->roles)) return 'pc';
        if (in_array('lccp_big_bird', $user->roles)) return 'bigbird';
        if (in_array('lccp_mentor', $user->roles)) return 'mentor';
        if (in_array('lccp_pc', $user->roles)) return 'student';
        return 'none';
    }

    public function ajax_add_timecode_marker() {
        check_ajax_referer('lccp_audio_review', 'nonce');

        $submission_id = intval($_POST['submission_id']);
        $timecode = floatval($_POST['timecode']);
        $category = sanitize_text_field($_POST['category']);
        $severity = sanitize_text_field($_POST['severity']);
        $note = sanitize_textarea_field($_POST['note']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_audio_markers';

        $user_id = get_current_user_id();
        $user_role = $this->get_user_role();

        $inserted = $wpdb->insert(
            $table_name,
            array(
                'submission_id' => $submission_id,
                'reviewer_id' => $user_id,
                'reviewer_role' => $user_role,
                'timecode' => $timecode,
                'note' => $note,
                'category' => $category,
                'severity' => $severity,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s')
        );

        if ($inserted) {
            wp_send_json_success(array('message' => 'Marker added successfully'));
        } else {
            wp_send_json_error('Failed to add marker');
        }
    }

    public function ajax_get_timecode_markers() {
        check_ajax_referer('lccp_audio_review', 'nonce');

        $submission_id = intval($_POST['submission_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_audio_markers';

        $markers = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as reviewer_name
             FROM $table_name m
             LEFT JOIN {$wpdb->users} u ON m.reviewer_id = u.ID
             WHERE m.submission_id = %d
             ORDER BY m.timecode ASC",
            $submission_id
        ));

        wp_send_json_success(array('markers' => $markers));
    }

    public function ajax_export_notes_pdf() {
        check_ajax_referer('lccp_audio_review', 'nonce');

        $submission_id = intval($_POST['submission_id']);

        // Get all markers for this submission
        global $wpdb;
        $markers_table = $wpdb->prefix . 'lccp_audio_markers';
        $submissions_table = $wpdb->prefix . 'lccp_hour_submissions';

        $markers = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as reviewer_name
             FROM $markers_table m
             LEFT JOIN {$wpdb->users} u ON m.reviewer_id = u.ID
             WHERE m.submission_id = %d
             ORDER BY m.timecode ASC",
            $submission_id
        ));

        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $submissions_table WHERE id = %d",
            $submission_id
        ));

        // Generate PDF content
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        $pdf_content = $this->generate_pdf_content($submission, $markers);

        // Save PDF to uploads directory
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/lccp-hour-notes/';
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        $filename = 'hour-notes-' . $submission_id . '-' . date('Y-m-d') . '.html';
        $filepath = $pdf_dir . $filename;

        file_put_contents($filepath, $pdf_content);

        $pdf_url = $upload_dir['baseurl'] . '/lccp-hour-notes/' . $filename;

        wp_send_json_success(array('pdf_url' => $pdf_url));
    }

    private function generate_pdf_content($submission, $markers) {
        $student = get_userdata($submission->student_id);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Hour Submission Review Notes</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
                h1 { color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px; }
                .header-info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .marker { margin-bottom: 20px; padding: 15px; border-left: 4px solid #ddd; background: #fafafa; }
                .marker.praise { border-left-color: #4CAF50; }
                .marker.info { border-left-color: #2196F3; }
                .marker.suggestion { border-left-color: #FFC107; }
                .marker.warning { border-left-color: #FF9800; }
                .marker.critical { border-left-color: #f44336; }
                .timecode { font-weight: bold; color: #2271b1; }
                .reviewer { color: #666; font-size: 0.9em; }
                .category { display: inline-block; padding: 2px 8px; background: #e0e0e0; border-radius: 3px; font-size: 0.9em; }
                @media print { .marker { page-break-inside: avoid; } }
            </style>
        </head>
        <body>
            <h1>Coaching Hour Review Notes</h1>

            <div class="header-info">
                <p><strong>Student:</strong> <?php echo esc_html($student->display_name); ?></p>
                <p><strong>Session Date:</strong> <?php echo date('F j, Y', strtotime($submission->session_date)); ?></p>
                <p><strong>Hours:</strong> <?php echo number_format($submission->hours, 1); ?></p>
                <p><strong>Session Type:</strong> <?php echo esc_html($submission->session_type); ?></p>
                <p><strong>Status:</strong> <?php echo ucfirst($submission->status); ?></p>
                <?php if ($submission->description): ?>
                <p><strong>Description:</strong> <?php echo esc_html($submission->description); ?></p>
                <?php endif; ?>
            </div>

            <h2>Review Notes & Feedback</h2>

            <?php if (empty($markers)): ?>
                <p>No review notes available for this submission.</p>
            <?php else: ?>
                <?php foreach ($markers as $marker): ?>
                <div class="marker <?php echo esc_attr($marker->severity); ?>">
                    <p>
                        <span class="timecode"><?php echo $this->format_timecode($marker->timecode); ?></span>
                        <span class="reviewer">by <?php echo esc_html($marker->reviewer_name); ?> (<?php echo esc_html($marker->reviewer_role); ?>)</span>
                    </p>
                    <p>
                        <span class="category"><?php echo esc_html($marker->category); ?></span>
                        <span class="category"><?php echo esc_html($marker->severity); ?></span>
                    </p>
                    <p><?php echo nl2br(esc_html($marker->note)); ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($submission->mentor_notes): ?>
            <h2>Final Mentor Notes</h2>
            <div class="marker info">
                <p><?php echo nl2br(esc_html($submission->mentor_notes)); ?></p>
            </div>
            <?php endif; ?>

            <p style="margin-top: 40px; text-align: center; color: #666;">
                Generated on <?php echo date('F j, Y \a\t g:i a'); ?>
            </p>
        </body>
        </html>
        <?php

        return ob_get_clean();
    }

    private function format_timecode($seconds) {
        $minutes = floor($seconds / 60);
        $secs = floor($seconds % 60);
        return sprintf('%d:%02d', $minutes, $secs);
    }

    public function render_student_notes($atts) {
        $atts = shortcode_atts(array(
            'submission_id' => 0
        ), $atts);

        if (!$atts['submission_id']) {
            return '<p>Invalid submission ID.</p>';
        }

        $user_id = get_current_user_id();

        // Get submission details
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lccp_hour_submissions';

        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $submissions_table WHERE id = %d AND student_id = %d",
            $atts['submission_id'],
            $user_id
        ));

        if (!$submission) {
            return '<p>Submission not found or access denied.</p>';
        }

        // Get markers
        $markers_table = $wpdb->prefix . 'lccp_audio_markers';
        $markers = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as reviewer_name
             FROM $markers_table m
             LEFT JOIN {$wpdb->users} u ON m.reviewer_id = u.ID
             WHERE m.submission_id = %d
             ORDER BY m.timecode ASC",
            $atts['submission_id']
        ));

        ob_start();
        ?>
        <div class="lccp-student-notes-view">
            <h2>Your Hour Submission Feedback</h2>

            <div class="lccp-submission-summary">
                <p><strong>Session Date:</strong> <?php echo date('F j, Y', strtotime($submission->session_date)); ?></p>
                <p><strong>Hours:</strong> <?php echo number_format($submission->hours, 1); ?></p>
                <p><strong>Status:</strong>
                    <span class="status-<?php echo $submission->status; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $submission->status)); ?>
                    </span>
                </p>
            </div>

            <?php if ($submission->audio_file_url): ?>
            <div class="lccp-audio-review">
                <?php echo do_shortcode('[lccp_audio_review_player submission_id="' . $atts['submission_id'] . '" audio_url="' . $submission->audio_file_url . '" mode="view"]'); ?>
            </div>
            <?php endif; ?>

            <?php if ($submission->mentor_notes): ?>
            <div class="lccp-mentor-feedback">
                <h3>Mentor Feedback</h3>
                <div class="feedback-content">
                    <?php echo nl2br(esc_html($submission->mentor_notes)); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }
}

// Initialize
LCCP_Audio_Review_Enhanced::get_instance();