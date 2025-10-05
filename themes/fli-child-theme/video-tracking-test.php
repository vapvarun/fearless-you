<?php
/**
 * Template Name: Video Tracking Test
 * Description: Test page for LearnDash video tracking functionality
 */

get_header();
?>

<div class="bb-grid site">
    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <article class="page">
                <div class="entry-content">
                    <h1>LearnDash Video Tracking Test</h1>
                    
                    <div class="test-info" style="background: #f0f0f0; padding: 20px; margin: 20px 0; border-radius: 5px;">
                        <h2>How the Video Tracking Works:</h2>
                        <ol>
                            <li><strong>Cumulative Tracking:</strong> Video watch progress is tracked cumulatively across all sessions</li>
                            <li><strong>75% Threshold:</strong> The "Mark Complete" button becomes available after watching 75% of the video</li>
                            <li><strong>Auto-Complete:</strong> The lesson/topic automatically completes when 100% of the video is watched</li>
                            <li><strong>Session Persistence:</strong> Progress is saved in localStorage and persists across browser sessions</li>
                        </ol>
                        
                        <h3>Testing Instructions:</h3>
                        <ol>
                            <li>Go to any LearnDash lesson or topic with a video</li>
                            <li>Start playing the video</li>
                            <li>Watch the progress indicator below the "Mark Complete" button</li>
                            <li>The button should enable at 75% completion</li>
                            <li>The lesson should auto-complete at 100%</li>
                        </ol>
                        
                        <h3>Debug Mode:</h3>
                        <p>Open your browser's Developer Console (F12) to see detailed tracking logs.</p>
                    </div>
                    
                    <div class="storage-viewer" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">
                        <h3>Current Video Progress Data:</h3>
                        <div id="storage-data" style="font-family: monospace; white-space: pre-wrap; background: #f5f5f5; padding: 15px; border-radius: 3px; margin-top: 10px;"></div>
                        <button onclick="refreshStorageData()" style="margin-top: 10px; padding: 10px 20px; background: #5A7891; color: white; border: none; border-radius: 3px; cursor: pointer;">Refresh Data</button>
                        <button onclick="clearVideoData()" style="margin-top: 10px; margin-left: 10px; padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;">Clear All Video Data</button>
                    </div>
                    
                    <script>
                        function refreshStorageData() {
                            const storageData = {};
                            
                            // Get all localStorage keys that start with our prefix
                            for (let i = 0; i < localStorage.length; i++) {
                                const key = localStorage.key(i);
                                if (key && key.startsWith('ld_cumulative_')) {
                                    try {
                                        storageData[key] = JSON.parse(localStorage.getItem(key));
                                    } catch (e) {
                                        storageData[key] = localStorage.getItem(key);
                                    }
                                }
                            }
                            
                            // Also get cookie data
                            const cookies = document.cookie.split(';');
                            const cookieData = {};
                            cookies.forEach(cookie => {
                                const [name, value] = cookie.trim().split('=');
                                if (name && name.startsWith('learndash-video-progress')) {
                                    try {
                                        cookieData[name] = JSON.parse(decodeURIComponent(value));
                                    } catch (e) {
                                        cookieData[name] = decodeURIComponent(value);
                                    }
                                }
                            });
                            
                            const output = {
                                localStorage: storageData,
                                cookies: cookieData
                            };
                            
                            document.getElementById('storage-data').textContent = JSON.stringify(output, null, 2);
                        }
                        
                        function clearVideoData() {
                            if (confirm('Are you sure you want to clear all video tracking data? This will reset all video progress.')) {
                                // Clear localStorage
                                const keysToRemove = [];
                                for (let i = 0; i < localStorage.length; i++) {
                                    const key = localStorage.key(i);
                                    if (key && key.startsWith('ld_cumulative_')) {
                                        keysToRemove.push(key);
                                    }
                                }
                                keysToRemove.forEach(key => localStorage.removeItem(key));
                                
                                // Clear cookies
                                const cookies = document.cookie.split(';');
                                cookies.forEach(cookie => {
                                    const [name] = cookie.trim().split('=');
                                    if (name && name.startsWith('learndash-video-progress')) {
                                        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                                    }
                                });
                                
                                alert('Video tracking data cleared!');
                                refreshStorageData();
                            }
                        }
                        
                        // Initial load
                        window.addEventListener('DOMContentLoaded', function() {
                            refreshStorageData();
                        });
                    </script>
                </div>
            </article>
        </main>
    </div>
</div>

<?php
get_footer();