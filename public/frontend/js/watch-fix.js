// @ts-nocheck
/**
 * Video Watching Fix - Patch for proper video streaming
 * Run this after the page loads to fix any video playback issues
 */

(function() {
    'use strict';

    console.log('Video Watch Fix loaded');

    // Override initializeVideoPlayer with improved version
    if (typeof initializeVideoPlayer === 'function') {
        const originalInitializeVideoPlayer = initializeVideoPlayer;

        window.initializeVideoPlayer = function(videoId, videoUrl) {
            const video = document.getElementById('video-player');
            if (!video) {
                console.error('Video player element not found');
                return;
            }

            console.log('Initializing video player for ID:', videoId);
            console.log('Video URL:', videoUrl);

            // Use the streaming endpoint directly
            const apiBaseUrl = 'http://127.0.0.1:8000/api';
            const streamUrl = videoUrl || `${apiBaseUrl}/videos/${videoId}/stream`;

            // Check if video source is already set correctly
            if (video.src && video.src.includes(streamUrl)) {
                console.log('Video source already set correctly');
                return;
            }

            video.src = streamUrl;
            video.preload = 'metadata';

            // Add error handling
            video.addEventListener('error', function(e) {
                console.error('Video error:', video.error);
                console.error('Video error code:', video.error ? video.error.code : 'N/A');

                if (video.error) {
                    switch(video.error.code) {
                        case 1:
                            console.error('Error: Video loading aborted');
                            break;
                        case 2:
                            console.error('Error: Network error - video could not be loaded');
                            break;
                        case 3:
                            console.error('Error: Video decoding error');
                            break;
                        case 4:
                            console.error('Error: Video format not supported');
                            break;
                    }
                }

                // Try to show more info
                showToast('Error loading video. Please try again.', 'danger');
            });

            // Add loadeddata event
            video.addEventListener('loadeddata', function() {
                console.log('Video data loaded successfully');
                showToast('Video loaded!', 'success');
            });

            // Track watch progress
            let lastProgressTime = 0;
            video.addEventListener('timeupdate', function() {
                const progress = Math.floor(video.currentTime);
                const duration = video.duration;

                if (progress > 0 && (progress - lastProgressTime >= 10 || video.ended)) {
                    if (typeof recordWatchProgress === 'function') {
                        recordWatchProgress(videoId, progress, video.ended);
                    }
                    lastProgressTime = progress;
                }
            });

            // Restore watch position
            if (typeof getVideoHistory === 'function') {
                getVideoHistory(videoId).then(function(history) {
                    if (history && history.progress > 0) {
                        const continueBtn = document.getElementById('continue-btn');
                        if (continueBtn) {
                            continueBtn.style.display = 'block';
                            continueBtn.textContent = 'Continue from ' + formatDuration(history.progress);
                            continueBtn.onclick = function() {
                                video.currentTime = history.progress;
                                video.play();
                            };
                        }
                    }
                });
            }
        };

        console.log('initializeVideoPlayer function patched');
    }

    // Override loadVideoDetails with improved version
    if (typeof loadVideoDetails === 'function') {
        const originalLoadVideoDetails = loadVideoDetails;

        window.loadVideoDetails = async function(videoId) {
            console.log('Loading video details for ID:', videoId);

            const apiBaseUrl = 'http://127.0.0.1:8000/api';

            try {
                const response = await fetch(`${apiBaseUrl}/videos/${videoId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load video: ' + response.status);
                }

                const data = await response.json();
                const video = data.data;

                if (!video) {
                    throw new Error('Video not found');
                }

                // Construct the streaming URL
                const videoUrl = `${apiBaseUrl}/videos/${videoId}/stream`;

                console.log('Video loaded:', video.title);
                console.log('Stream URL:', videoUrl);

                return {
                    ...video,
                    video_url: videoUrl,
                    thumbnail_url: video.thumbnail_url || (video.thumbnail_path ? `http://127.0.0.1:8000/storage/${video.thumbnail_path}` : null),
                    video_file_url: video.video_file_url || (video.file_path ? `http://127.0.0.1:8000/storage/${video.file_path}` : null)
                };
            } catch (error) {
                console.error('Error loading video details:', error);
                return null;
            }
        };

        console.log('loadVideoDetails function patched');
    }

    // Add global function to test video streaming
    window.testVideoStream = async function(videoId) {
        const apiBaseUrl = 'http://127.0.0.1:8000/api';

        console.log('Testing video stream for ID:', videoId);

        // Test 1: Check API response
        try {
            const response = await fetch(`${apiBaseUrl}/videos/${videoId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            console.log('API Response:', data);

            if (data.data && data.data.file_path) {
                console.log('Video file path:', data.data.file_path);

                // Test 2: Check if file exists in storage
                const filePath = data.data.file_path;
                const fullPath = `/home/prosper/itechtubefront/storage/app/public/${filePath}`;
                const fileExists = await fetch(`${apiBaseUrl}/videos/${videoId}/stream`, { method: 'HEAD' })
                    .then(res => {
                        console.log('Stream endpoint status:', res.status);
                        return res.ok;
                    })
                    .catch(err => {
                        console.error('Stream test failed:', err);
                        return false;
                    });

                console.log('Stream accessible:', fileExists);
                return fileExists;
            }
        } catch (error) {
            console.error('Test failed:', error);
            return false;
        }

        return false;
    };

    console.log('Video Watch Fix initialized');
    console.log('Use testVideoStream(videoId) to test streaming');
})();
