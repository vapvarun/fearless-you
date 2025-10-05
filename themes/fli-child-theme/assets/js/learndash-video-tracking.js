/**
 * Custom LearnDash Video Tracking with 75% Threshold
 * Tracks cumulative video watch time across sessions and enables mark complete at 75%
 */

(function($) {
    'use strict';

    // Wait for document ready
    $(document).ready(function() {
        // Check if we have video data
        if (typeof learndash_video_data === 'undefined') {
            return;
        }

        // Storage key prefix for cumulative tracking
        const CUMULATIVE_PREFIX = 'ld_cumulative_';
        
        // Threshold for showing mark complete button (75%)
        const COMPLETION_THRESHOLD = 0.75;
        
        // Auto-complete threshold (100%)
        const AUTO_COMPLETE_THRESHOLD = 0.95; // Using 95% to account for slight timing variations

        // Override the original LearnDash_Video_Progress_setSetting function
        const originalSetSetting = window.LearnDash_Video_Progress_setSetting;
        
        // Track cumulative watch time
        const cumulativeData = {};

        /**
         * Get cumulative data for a video
         */
        function getCumulativeData(cookieKey) {
            const key = CUMULATIVE_PREFIX + cookieKey;
            const data = localStorage.getItem(key);
            
            if (data) {
                try {
                    return JSON.parse(data);
                } catch (e) {
                    console.error('Error parsing cumulative data:', e);
                }
            }
            
            return {
                totalWatched: 0,
                segments: [],
                videoDuration: 0,
                lastUpdate: Date.now()
            };
        }

        /**
         * Save cumulative data for a video
         */
        function saveCumulativeData(cookieKey, data) {
            const key = CUMULATIVE_PREFIX + cookieKey;
            data.lastUpdate = Date.now();
            localStorage.setItem(key, JSON.stringify(data));
        }

        /**
         * Add a watched segment to cumulative data
         */
        function addWatchedSegment(cookieKey, startTime, endTime, duration) {
            if (!cookieKey || startTime >= endTime) return;
            
            const data = getCumulativeData(cookieKey);
            
            // Update video duration if available
            if (duration > 0) {
                data.videoDuration = duration;
            }
            
            // Add new segment
            data.segments.push({
                start: startTime,
                end: endTime
            });
            
            // Merge overlapping segments
            data.segments = mergeOverlappingSegments(data.segments);
            
            // Calculate total watched time
            data.totalWatched = calculateTotalWatched(data.segments);
            
            saveCumulativeData(cookieKey, data);
            
            return data;
        }

        /**
         * Merge overlapping segments
         */
        function mergeOverlappingSegments(segments) {
            if (segments.length <= 1) return segments;
            
            // Sort segments by start time
            segments.sort((a, b) => a.start - b.start);
            
            const merged = [segments[0]];
            
            for (let i = 1; i < segments.length; i++) {
                const current = segments[i];
                const last = merged[merged.length - 1];
                
                // Check if segments overlap or are adjacent
                if (current.start <= last.end) {
                    // Merge segments
                    last.end = Math.max(last.end, current.end);
                } else {
                    // Add as new segment
                    merged.push(current);
                }
            }
            
            return merged;
        }

        /**
         * Calculate total watched time from segments
         */
        function calculateTotalWatched(segments) {
            return segments.reduce((total, segment) => {
                return total + (segment.end - segment.start);
            }, 0);
        }

        /**
         * Check if completion threshold is reached
         */
        function checkCompletionThreshold(cookieKey) {
            const data = getCumulativeData(cookieKey);
            
            if (data.videoDuration <= 0) {
                return { percentage: 0, shouldEnableButton: false, shouldAutoComplete: false };
            }
            
            const percentage = data.totalWatched / data.videoDuration;
            
            return {
                percentage: percentage,
                shouldEnableButton: percentage >= COMPLETION_THRESHOLD,
                shouldAutoComplete: percentage >= AUTO_COMPLETE_THRESHOLD
            };
        }

        // Track current video position for each player
        const playerPositions = {};

        // Override the setting function to track cumulative progress
        window.LearnDash_Video_Progress_setSetting = function(ld_video_player, player_setting_key, player_setting_value) {
            // Call original function first to maintain compatibility
            if (originalSetSetting) {
                originalSetSetting.call(this, ld_video_player, player_setting_key, player_setting_value);
            }
            
            if (!ld_video_player || !ld_video_player.player_cookie_key) {
                return;
            }
            
            const cookieKey = ld_video_player.player_cookie_key;
            
            // Initialize player position tracking
            if (!playerPositions[cookieKey]) {
                playerPositions[cookieKey] = {
                    lastPosition: 0,
                    sessionStart: 0,
                    isPlaying: false
                };
            }
            
            // Track video duration
            if (player_setting_key === 'video_duration' && player_setting_value > 0) {
                const data = getCumulativeData(cookieKey);
                data.videoDuration = parseInt(player_setting_value);
                saveCumulativeData(cookieKey, data);
            }
            
            // Track video time updates
            if (player_setting_key === 'video_time') {
                const currentTime = parseInt(player_setting_value);
                const position = playerPositions[cookieKey];
                
                // Update position
                position.lastPosition = currentTime;
                
                // If playing, track the segment
                if (position.isPlaying && position.sessionStart < currentTime) {
                    const duration = ld_video_player.player_cookie_values.video_duration || 0;
                    addWatchedSegment(cookieKey, position.sessionStart, currentTime, duration);
                    
                    // Check if we should enable the button or auto-complete
                    const completion = checkCompletionThreshold(cookieKey);
                    
                    if (completion.shouldAutoComplete) {
                        // Auto-complete the lesson
                        console.log('Video watched 100% - auto-completing lesson');
                        
                        // Mark video as complete using original function to avoid infinite loop
                        if (originalSetSetting) {
                            originalSetSetting.call(this, ld_video_player, 'video_state', 'complete');
                        }
                        
                        // Enable the button and submit the form
                        if (typeof LearnDash_disable_assets !== 'undefined') {
                            LearnDash_disable_assets(false);
                        }
                        
                        // Auto-submit the form after a short delay
                        setTimeout(function() {
                            const $form = $('form.sfwd-mark-complete');
                            if ($form.length && !$form.hasClass('ld-video-auto-submitted')) {
                                $form.addClass('ld-video-auto-submitted');
                                // Only auto-submit if button is not manually clicked
                                if (!$form.data('manual-submit')) {
                                    console.log('Auto-submitting mark complete form');
                                    $form[0].submit();
                                }
                            }
                        }, 1000);
                    } else if (completion.shouldEnableButton) {
                        // Enable the mark complete button at 75%
                        console.log('Video watched ' + Math.round(completion.percentage * 100) + '% - enabling mark complete button');
                        
                        if (typeof LearnDash_disable_assets !== 'undefined') {
                            LearnDash_disable_assets(false);
                        }
                    }
                }
            }
            
            // Track play/pause state
            if (player_setting_key === 'video_state') {
                const position = playerPositions[cookieKey];
                
                if (player_setting_value === 'play') {
                    position.isPlaying = true;
                    position.sessionStart = position.lastPosition;
                } else if (player_setting_value === 'pause' || player_setting_value === 'complete') {
                    if (position.isPlaying && position.sessionStart < position.lastPosition) {
                        const duration = ld_video_player.player_cookie_values.video_duration || 0;
                        addWatchedSegment(cookieKey, position.sessionStart, position.lastPosition, duration);
                    }
                    position.isPlaying = false;
                }
                
                // If video is marked as complete by original logic, ensure we save final segment
                if (player_setting_value === 'complete') {
                    const completion = checkCompletionThreshold(cookieKey);
                    console.log('Video ended - total watched: ' + Math.round(completion.percentage * 100) + '%');
                }
            }
        };

        // Check initial state when page loads
        setTimeout(function() {
            if (typeof ld_video_players !== 'undefined') {
                for (let key in ld_video_players) {
                    if (ld_video_players.hasOwnProperty(key)) {
                        const player = ld_video_players[key];
                        if (player.player_cookie_key) {
                            const completion = checkCompletionThreshold(player.player_cookie_key);
                            
                            if (completion.shouldEnableButton) {
                                console.log('Initial check - video watched ' + Math.round(completion.percentage * 100) + '% - enabling mark complete button');
                                
                                if (typeof LearnDash_disable_assets !== 'undefined') {
                                    LearnDash_disable_assets(false);
                                }
                            }
                        }
                    }
                }
            }
        }, 2000);

        // Handle manual button clicks
        $(document).on('click', 'form.sfwd-mark-complete .learndash_mark_complete_button', function(e) {
            const $form = $(this).closest('form');
            console.log('Manual mark complete button clicked');
            
            // Mark as manual submission
            $form.data('manual-submit', true);
            
            // Remove auto-submitted class to allow manual submission
            $form.removeClass('ld-video-auto-submitted');
            
            // Allow the form to submit normally
            return true;
        });

        // Add visual indicator of progress
        $(document).on('learndash_video_disable_assets', function(e, status) {
            if (status === true) {
                // Video is playing, check if we should show progress
                setTimeout(function() {
                    updateProgressIndicator();
                }, 500);
            }
        });
        
        // Update progress indicator
        function updateProgressIndicator() {
            if (typeof ld_video_players !== 'undefined') {
                for (let key in ld_video_players) {
                    if (ld_video_players.hasOwnProperty(key)) {
                        const player = ld_video_players[key];
                        if (player.player_cookie_key) {
                            const completion = checkCompletionThreshold(player.player_cookie_key);
                            
                            // Add progress indicator
                            const $button = $('form.sfwd-mark-complete .learndash_mark_complete_button');
                            if ($button.length) {
                                let $progress = $button.parent().find('.ld-video-progress-indicator');
                                if (!$progress.length) {
                                    $progress = $('<div class="ld-video-progress-indicator"></div>').insertAfter($button);
                                }
                                
                                const percentage = Math.round(completion.percentage * 100);
                                $progress.html('<span style="color: #666; font-size: 14px;">Video Progress: ' + percentage + '%' + 
                                    (percentage >= 75 ? ' - You can now mark as complete!' : ' - Watch 75% to unlock completion') + '</span>');
                            }
                        }
                    }
                }
            }
        }
        
        // Set up periodic progress check (every 5 seconds while video is playing)
        let progressCheckInterval = null;
        
        // Monitor video state changes
        const originalDisableAssets = window.LearnDash_disable_assets;
        window.LearnDash_disable_assets = function(status) {
            // Call original function
            if (originalDisableAssets) {
                originalDisableAssets.apply(this, arguments);
            }
            
            if (status === true) {
                // Video started, begin periodic checks
                if (progressCheckInterval) {
                    clearInterval(progressCheckInterval);
                }
                
                progressCheckInterval = setInterval(function() {
                    updateProgressIndicator();
                    
                    // Also check if we should enable completion
                    if (typeof ld_video_players !== 'undefined') {
                        for (let key in ld_video_players) {
                            if (ld_video_players.hasOwnProperty(key)) {
                                const player = ld_video_players[key];
                                if (player.player_cookie_key) {
                                    const completion = checkCompletionThreshold(player.player_cookie_key);
                                    
                                    if (completion.shouldEnableButton && $('form.sfwd-mark-complete .learndash_mark_complete_button').prop('disabled')) {
                                        console.log('Periodic check: Enabling mark complete button at ' + Math.round(completion.percentage * 100) + '%');
                                        LearnDash_disable_assets(false);
                                    }
                                }
                            }
                        }
                    }
                }, 5000);
            } else {
                // Video stopped, clear interval
                if (progressCheckInterval) {
                    clearInterval(progressCheckInterval);
                    progressCheckInterval = null;
                }
            }
        };
    });
})(jQuery);