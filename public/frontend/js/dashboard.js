/**
 * iTechTube - Dashboard Module
 * Handles all dashboard-specific functionality with real-time API integration
 */

// Dashboard initialization
document.addEventListener('DOMContentLoaded', function() {
    if (!isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    const user = getCurrentUser();
    if (user && document.getElementById('user-name')) {
        document.getElementById('user-name').textContent = user.name;
    }

    const userRole = user?.role || 'user';
    document.body.classList.add('is-' + userRole);
    applyRoleBasedVisibility();

    initializeDashboard(userRole);
});

async function initializeDashboard(userRole) {
    // Load creator-specific data if user is creator or admin
    if (userRole === 'creator' || userRole === 'admin') {
        await loadCreatorChannelProfile();
        await checkStreamStatus();
        // Start real-time stats polling for creators/admins
        startChannelStatsPolling();
    }

    // Load common data for all users
    await populateCategorySelect();
    loadMyVideosList();
    loadPlaylistsList();
    loadContinueWatchingList();
}

// Stop polling when page unloads
window.addEventListener('beforeunload', function() {
    stopChannelStatsPolling();
    stopStreamStatusPolling();
});

// ============================================
// CHANNEL PROFILE FUNCTIONS (Real-time API)
// ============================================

/**
 * Load creator channel profile from API
 * Fixed to properly handle avatar and banner URLs
 */
async function loadCreatorChannelProfile() {
    try {
        const response = await apiRequest('/creator/channel');
        console.log('Channel profile response:', response);
        if (response.success && response.data) {
            const data = response.data;

            // Update channel name field
            const channelNameInput = document.getElementById('channel-name');
            if (channelNameInput) {
                channelNameInput.value = data.channel_name || '';
            }

            // Update channel description field
            const channelDescInput = document.getElementById('channel-description');
            if (channelDescInput) {
                channelDescInput.value = data.channel_description || '';
            }

            // Update stream key if available
            const streamKeyInput = document.getElementById('stream-key');
            if (streamKeyInput && data.stream_key_masked) {
                streamKeyInput.value = data.stream_key_masked;
            }

            // Update channel header avatar with proper URL
            const avatarImg = document.getElementById('channel-avatar-img');
            const avatarPlaceholder = document.getElementById('channel-avatar-placeholder');
            const avatarInitial = document.getElementById('channel-avatar-initial');

            if (data.avatar) {
                // Use the avatar URL directly from API response
                if (avatarImg) {
                    avatarImg.src = data.avatar;
                    avatarImg.style.display = 'block';
                }
                if (avatarPlaceholder) {
                    avatarPlaceholder.style.display = 'none';
                }
            } else {
                // Show placeholder with initial
                if (avatarImg) {
                    avatarImg.style.display = 'none';
                }
                if (avatarPlaceholder) {
                    avatarPlaceholder.style.display = 'flex';
                    const initial = (data.channel_name || data.name || 'C').charAt(0).toUpperCase();
                    if (avatarInitial) avatarInitial.textContent = initial;
                }
            }

            // Update channel banner with proper URL
            const bannerImg = document.getElementById('channel-banner-img');
            if (data.channel_banner) {
                if (bannerImg) {
                    bannerImg.src = data.channel_banner;
                    bannerImg.style.display = 'block';
                }
            } else {
                if (bannerImg) {
                    bannerImg.style.display = 'none';
                }
            }

            console.log('Channel profile loaded successfully');
        }
    } catch (error) {
        console.error('Error loading channel profile:', error);
    }
}

/**
 * Update channel profile via API
 * Fixed to properly handle avatar and banner uploads
 */
async function updateChannelProfile() {
    const user = getCurrentUser();
    if (user?.role !== 'creator' && user?.role !== 'admin') {
        showAlert('Only creators can update channel profile', 'warning');
        return;
    }

    const channelName = document.getElementById('channel-name')?.value.trim();
    const channelDescription = document.getElementById('channel-description')?.value.trim();
    const channelAvatar = document.getElementById('channel-avatar-input')?.files[0];
    const channelBanner = document.getElementById('channel-banner-input')?.files[0];

    if (!channelName) {
        showAlert('Please enter a channel name', 'warning');
        return;
    }

    const btn = document.querySelector('#creator-profile-panel .btn-danger');
    const originalText = btn ? btn.innerHTML : 'Update Profile';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="loading"></span> Updating...';
    }

    try {
        const formData = new FormData();
        formData.append('channel_name', channelName);
        if (channelDescription) {
            formData.append('channel_description', channelDescription);
        }
        if (channelAvatar) {
            formData.append('avatar', channelAvatar);
        }
        if (channelBanner) {
            formData.append('channel_banner', channelBanner);
        }

        console.log('Updating channel profile with data:', {
            channelName,
            channelDescription,
            hasAvatar: !!channelAvatar,
            hasBanner: !!channelBanner
        });

        const response = await apiRequest('/creator/channel', {
            method: 'PUT',
            body: formData
        });

        console.log('Update response:', response);

        if (response.success) {
            showAlert('Channel profile updated successfully!', 'success');

            // Reload channel profile to update UI
            await loadCreatorChannelProfile();

            // Also update the user object in localStorage if avatar changed
            if (channelAvatar) {
                // Read the uploaded file and update localStorage
                const reader = new FileReader();
                reader.onload = function(e) {
                    const updatedUser = { ...user, avatar: e.target.result };
                    setCurrentUser(updatedUser);
                };
                reader.readAsDataURL(channelAvatar);
            }
        } else {
            showAlert('Failed to update channel: ' + (response.message || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Error updating channel profile:', error);
        showAlert('Error updating channel: ' + error.message, 'danger');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
}

// ============================================
// STREAMING FUNCTIONS (Real-time API)
// ============================================

/**
 * Check current stream status from API
 */
async function checkStreamStatus() {
    try {
        const response = await apiRequest('/creator/stream/status');
        if (response.success && response.data) {
            const data = response.data;
            updateStreamUI(data);
        }
    } catch (error) {
        console.error('Error checking stream status:', error);
    }
}

/**
 * Update stream UI based on status
 */
function updateStreamUI(streamData) {
    const startBtn = document.getElementById('start-stream-btn');
    const stopBtn = document.getElementById('stop-stream-btn');
    const statusBadge = document.getElementById('current-stream-status');
    const viewersEl = document.getElementById('live-viewer-count');
    const durEl = document.getElementById('stream-duration');

    if (streamData.stream_status === 'live') {
        if (startBtn) startBtn.classList.add('d-none');
        if (stopBtn) stopBtn.classList.remove('d-none');
        if (statusBadge) {
            statusBadge.textContent = 'Live';
            statusBadge.className = 'badge bg-danger';
        }
        if (streamData.stream_title) {
            const titleInput = document.getElementById('stream-title');
            if (titleInput) titleInput.value = streamData.stream_title;
        }

        // Start duration timer
        if (streamData.stream_duration_formatted && durEl) {
            durEl.textContent = streamData.stream_duration_formatted;
        }

        // Start viewer updates
        startStreamStatusPolling();
    } else {
        if (startBtn) startBtn.classList.remove('d-none');
        if (stopBtn) stopBtn.classList.add('d-none');
        if (statusBadge) {
            statusBadge.textContent = 'Offline';
            statusBadge.className = 'badge bg-secondary';
        }
        if (viewersEl) viewersEl.textContent = '0';
        if (durEl) durEl.textContent = '00:00:00';
    }
}

let streamStatusInterval = null;

/**
 * Start polling for stream status updates
 */
function startStreamStatusPolling() {
    if (streamStatusInterval) {
        clearInterval(streamStatusInterval);
    }

    streamStatusInterval = setInterval(async function() {
        try {
            const response = await apiRequest('/creator/stream/status');
            if (response.success && response.data) {
                const data = response.data;

                // Update viewers count
                const viewersEl = document.getElementById('live-viewer-count');
                if (viewersEl && data.stream_viewers !== undefined) {
                    viewersEl.textContent = data.stream_viewers;
                }

                // Update duration
                const durEl = document.getElementById('stream-duration');
                if (durEl && data.stream_duration_formatted) {
                    durEl.textContent = data.stream_duration_formatted;
                }

                // Check if stream ended
                if (data.stream_status !== 'live') {
                    stopStreamStatusPolling();
                    updateStreamUI(data);
                }
            }
        } catch (error) {
            console.error('Error polling stream status:', error);
        }
    }, 5000); // Poll every 5 seconds
}

/**
 * Stop polling for stream status
 */
function stopStreamStatusPolling() {
    if (streamStatusInterval) {
        clearInterval(streamStatusInterval);
        streamStatusInterval = null;
    }
}

/**
 * Get stream key from API
 */
async function getStreamKey() {
    try {
        const response = await apiRequest('/creator/stream/key');
        if (response.success && response.data) {
            return response.data;
        }
    } catch (error) {
        console.error('Error getting stream key:', error);
    }
    return null;
}

/**
 * Regenerate stream key via API
 */
async function regenerateStreamKey() {
    const user = getCurrentUser();
    if (user?.role !== 'creator' && user?.role !== 'admin') {
        showAlert('Only creators can manage streaming', 'warning');
        return;
    }

    if (!confirm('Regenerate stream key? Current key will stop working.')) return;

    try {
        const response = await apiRequest('/creator/stream/regenerate-key', {
            method: 'POST'
        });

        if (response.success) {
            const keyInput = document.getElementById('stream-key');
            if (keyInput && response.data.stream_key_masked) {
                keyInput.value = response.data.stream_key_masked;
            }
            showToast('Stream key regenerated!', 'success');

            // Show the full key once (in production, this should be shown only once)
            alert('Your new stream key: ' + response.data.stream_key);
        } else {
            showAlert('Failed to regenerate stream key: ' + (response.message || 'Unknown error'), 'danger');
        }
    } catch (error) {
        showAlert('Error regenerating stream key: ' + error.message, 'danger');
    }
}

/**
 * Start live stream via API
 */
async function startStream() {
    const user = getCurrentUser();
    if (user?.role !== 'creator' && user?.role !== 'admin') {
        showAlert('Only creators can start streams', 'warning');
        return;
    }

    const title = document.getElementById('stream-title')?.value.trim();
    if (!title) {
        showAlert('Enter stream title', 'warning');
        return;
    }

    try {
        const response = await apiRequest('/creator/stream/start', {
            method: 'POST',
            body: JSON.stringify({ title: title })
        });

        if (response.success) {
            showToast('Stream started!', 'success');
            updateStreamUI(response.data);
            startStreamStatusPolling();

            // Show RTMP information
            if (response.data.stream_url && response.data.stream_key) {
                console.log('Stream RTMP URL:', response.data.stream_url);
                console.log('Stream Key:', response.data.stream_key);
            }
        } else {
            showAlert('Failed to start stream: ' + (response.message || 'Unknown error'), 'danger');
        }
    } catch (error) {
        showAlert('Error starting stream: ' + error.message, 'danger');
    }
}

/**
 * Stop live stream via API
 */
async function stopStream() {
    if (!confirm('End stream?')) return;

    try {
        const response = await apiRequest('/creator/stream/stop', {
            method: 'POST'
        });

        if (response.success) {
            stopStreamStatusPolling();
            showToast('Stream ended', 'info');

            // Update UI
            const startBtn = document.getElementById('start-stream-btn');
            const stopBtn = document.getElementById('stop-stream-btn');
            const statusBadge = document.getElementById('current-stream-status');
            const viewersEl = document.getElementById('live-viewer-count');
            const durEl = document.getElementById('stream-duration');

            if (startBtn) startBtn.classList.remove('d-none');
            if (stopBtn) stopBtn.classList.add('d-none');
            if (statusBadge) {
                statusBadge.textContent = 'Offline';
                statusBadge.className = 'badge bg-secondary';
            }
            if (viewersEl) viewersEl.textContent = '0';
            if (durEl) durEl.textContent = '00:00:00';

            // Clear title
            const titleInput = document.getElementById('stream-title');
            if (titleInput) titleInput.value = '';
        } else {
            showAlert('Failed to end stream: ' + (response.message || 'Unknown error'), 'danger');
        }
    } catch (error) {
        showAlert('Error ending stream: ' + error.message, 'danger');
    }
}

// ============================================
// CHANNEL STATISTICS FUNCTIONS
// ============================================

/**
 * Load channel statistics
 */
async function loadChannelStats() {
    try {
        const response = await apiRequest('/creator/stats');
        if (response.success && response.data) {
            return response.data;
        }
    } catch (error) {
        console.error('Error loading channel stats:', error);
    }
    return null;
}

// ============================================
// VIDEO LIST FUNCTIONS (FIXED)
// ============================================

// Load My Videos
async function loadMyVideosList() {
    const container = document.getElementById('my-videos-container');
    if (!container) return;
    container.innerHTML = '<div class="col-12 text-center text-muted"><div class="loading"></div><p>Loading videos...</p></div>';
    try {
        const response = await loadMyVideos();
        console.log('My Videos Response:', response);

        // Handle the response structure properly
        let videos = [];
        if (response && response.data) {
            videos = response.data;
        } else if (Array.isArray(response)) {
            videos = response;
        }

        if (videos && videos.length > 0) {
            container.innerHTML = videos.map(function(video) {
                return createVideoCard(video, true);
            }).join('');
        } else {
            container.innerHTML = '<div class="col-12 text-center text-muted"><p>No videos yet. Upload your first video!</p></div>';
        }
    } catch (error) {
        console.error('Error loading my videos:', error);
        container.innerHTML = '<div class="col-12 text-center text-danger">Error: ' + error.message + '</div>';
    }
}

// Load Playlists
async function loadPlaylistsList() {
    const container = document.getElementById('playlists-container');
    if (!container) return;
    container.innerHTML = '<div class="col-12 text-center text-muted"><div class="loading"></div><p>Loading...</p></div>';
    try {
        const playlists = await loadPlaylists();
        if (playlists && playlists.length > 0) {
            container.innerHTML = playlists.map(function(p) {
                return '<div class="col-md-4 mb-3"><div class="card h-100"><div class="card-body"><h5 class="card-title text-yellow">' + escapeHtml(p.name) + '</h5><p class="card-text small">' + escapeHtml(p.description || 'No description') + '</p></div><div class="card-footer bg-transparent"><button class="btn btn-sm btn-danger" onclick="deletePlaylistHandler(' + p.id + ')">Delete</button></div></div></div>';
            }).join('');
        } else {
            container.innerHTML = '<div class="col-12 text-center text-muted"><p>No playlists yet.</p></div>';
        }
    } catch (error) {
        console.error('Error loading playlists:', error);
        container.innerHTML = '<div class="col-12 text-center text-danger">Error: ' + error.message + '</div>';
    }
}

// Load Continue Watching
async function loadContinueWatchingList() {
    const container = document.getElementById('continue-watching-container');
    if (!container) return;
    container.innerHTML = '<div class="col-12 text-center text-muted"><div class="loading"></div><p>Loading...</p></div>';
    try {
        const history = await loadContinueWatching();
        if (history && history.length > 0) {
            container.innerHTML = history.map(function(item) {
                var v = item.video;
                var thumb = getVideoThumbnail(v);
                return '<div class="col-md-3 mb-3"><div class="card h-100"><a href="video.html?id=' + v.id + '"><img src="' + thumb + '" class="card-img-top" alt="' + escapeHtml(v.title) + '" onerror="this.src=\'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22200%22 viewBox=%220 0 400 200%22><rect fill=%22%23333%22 width=%22400%22 height=%22200%22/><text fill=%22%23666%22 font-family=%22Arial%22 font-size=%2220%22 x=%2250%%22 y=%2250%%22 text-anchor=%22middle%22>No Thumbnail</text></svg>\'"></a><div class="card-body"><h6 class="card-title">' + escapeHtml(v.title) + '</h6></div></div></div>';
            }).join('');
        } else {
            container.innerHTML = '<div class="col-12 text-center text-muted">No watch history.</div>';
        }
    } catch (error) {
        console.error('Error loading continue watching:', error);
        container.innerHTML = '<div class="col-12 text-center text-danger">Error: ' + error.message + '</div>';
    }
}

// ============================================
// PLAYLIST FUNCTIONS
// ============================================

// Create Playlist
document.getElementById('create-playlist-btn')?.addEventListener('click', async function() {
    var name = document.getElementById('playlist-name')?.value.trim();
    if (!name) { showAlert('Enter playlist name', 'warning'); return; }
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span>...';
    try {
        await createPlaylist(name, (document.getElementById('playlist-description')?.value || '').trim(), document.getElementById('playlist-public')?.checked || false);
        showAlert('Playlist created!', 'success');
        var modal = bootstrap.Modal.getInstance(document.getElementById('createPlaylistModal'));
        if (modal) modal.hide();
        var form = document.getElementById('create-playlist-form');
        if (form) form.reset();
        loadPlaylistsList();
    } catch (error) { showAlert('Error: ' + error.message, 'danger'); }
    finally { btn.disabled = false; btn.innerHTML = 'Create Playlist'; }
});

// Delete Playlist
async function deletePlaylistHandler(id) {
    if (!confirm('Delete playlist?')) return;
    try {
        await deletePlaylist(id);
        loadPlaylistsList();
    } catch (error) { showAlert('Error: ' + error.message, 'danger'); }
}

// ============================================
// EXPORT FUNCTIONS TO WINDOW
// ============================================

window.loadMyVideosList = loadMyVideosList;
window.loadPlaylistsList = loadPlaylistsList;
window.loadContinueWatchingList = loadContinueWatchingList;
window.deletePlaylistHandler = deletePlaylistHandler;
window.updateChannelProfile = updateChannelProfile;
window.regenerateStreamKey = regenerateStreamKey;
window.startStream = startStream;
window.stopStream = stopStream;
window.loadCreatorChannelProfile = loadCreatorChannelProfile;
window.checkStreamStatus = checkStreamStatus;
window.loadChannelStats = loadChannelStats;

// ============================================
// REAL-TIME CHANNEL STATS POLLING
// ============================================

let channelStatsInterval = null;
const CHANNEL_STATS_POLL_INTERVAL = 10000; // 10 seconds

/**
 * Start polling for real-time channel statistics
 * Updates subscribers, videos, and views counts
 */
function startChannelStatsPolling() {
    if (channelStatsInterval) {
        clearInterval(channelStatsInterval);
    }

    // Load immediately, then poll
    updateChannelStats();

    channelStatsInterval = setInterval(async function() {
        try {
            await updateChannelStats();
        } catch (error) {
            console.error('Error polling channel stats:', error);
        }
    }, CHANNEL_STATS_POLL_INTERVAL);

    console.log('Channel stats polling started (every ' + (CHANNEL_STATS_POLL_INTERVAL / 1000) + ' seconds)');
}

/**
 * Stop polling for channel stats
 */
function stopChannelStatsPolling() {
    if (channelStatsInterval) {
        clearInterval(channelStatsInterval);
        channelStatsInterval = null;
        console.log('Channel stats polling stopped');
    }
}

/**
 * Update channel statistics in the UI
 */
async function updateChannelStats() {
    try {
        const response = await apiRequest('/creator/channel');
        if (response.success && response.data) {
            const data = response.data;

            // Update stats in channel header
            const subscribersEl = document.getElementById('channel-subscribers');
            const videosEl = document.getElementById('channel-videos');
            const viewsEl = document.getElementById('channel-views');

            if (subscribersEl) {
                subscribersEl.textContent = formatNumber(data.total_subscribers || 0);
            }
            if (videosEl) {
                videosEl.textContent = formatNumber(data.videos_count || 0);
            }
            if (viewsEl) {
                viewsEl.textContent = formatNumber(data.total_views || 0);
            }

            // Also update stream status badge
            const statusBadge = document.getElementById('stream-status-badge');
            if (statusBadge) {
                if (data.stream_status === 'live') {
                    statusBadge.textContent = '🔴 LIVE';
                    statusBadge.className = 'badge bg-danger live-badge';
                } else {
                    statusBadge.textContent = 'Offline';
                    statusBadge.className = 'badge bg-secondary';
                }
            }
        }
    } catch (error) {
        console.error('Error updating channel stats:', error);
    }
}

// Export polling functions to window
window.startChannelStatsPolling = startChannelStatsPolling;
window.stopChannelStatsPolling = stopChannelStatsPolling;
window.updateChannelStats = updateChannelStats;

