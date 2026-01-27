// @ts-nocheck
/**
 * iTechTube - Video Streaming Platform Frontend
 * Complete JavaScript application with all API integrations
 */

// ============================================
// CONFIGURATION
// ============================================
const API_BASE_URL = 'http://127.0.0.1:8000/api';
const STORAGE_URL = 'http://127.0.0.1:8000/storage';

// Chunked upload configuration
const CHUNKED_UPLOAD_CONFIG = {
    CHUNK_SIZE: 10 * 1024 * 1024, // 10MB per chunk
    MAX_RETRIES: 3,
    RETRY_DELAY: 1000, // 1 second
    PARALLEL_UPLOADS: 2, // Number of concurrent chunk uploads
};

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Show loading spinner in an element
 */
function showLoading(element) {
    element.innerHTML = '<div class="loading"></div>';
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info', container = null) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const targetContainer = container || document.querySelector('.container');
    if (targetContainer) {
        targetContainer.prepend(alertDiv);
    }

    setTimeout(() => alertDiv.remove(), 5000);
}

/**
 * Format date to readable string
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

/**
 * Format duration from seconds to MM:SS or HH:MM:SS
 */
function formatDuration(seconds) {
    if (!seconds) return '0:00';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${minutes}:${secs.toString().padStart(2, '0')}`;
}

/**
 * Format number with commas
 */
function formatNumber(num) {
    if (!num) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Format time to "time ago" string (e.g., "2 hours ago")
 */
function formatTimeAgo(dateString) {
    if (!dateString) return 'Unknown';

    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);

    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`;
    if (diff < 2592000) return `${Math.floor(diff / 604800)} weeks ago`;
    return formatDate(dateString);
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container') || document.body;
    const toastId = 'toast-' + Date.now();

    const toastDiv = document.createElement('div');
    toastDiv.id = toastId;
    toastDiv.className = `toast align-items-center text-bg-${type} border-0`;
    toastDiv.setAttribute('role', 'alert');
    toastDiv.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    container.appendChild(toastDiv);

    const bsToast = new bootstrap.Toast(toastDiv, { autohide: true, delay: 3000 });
    bsToast.show();

    toastDiv.addEventListener('hidden.bs.toast', () => {
        toastDiv.remove();
    });
}

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

/**
 * Get auth token from localStorage
 */
function getAuthToken() {
    return localStorage.getItem('auth_token');
}

/**
 * Set auth token
 */
function setAuthToken(token) {
    localStorage.setItem('auth_token', token);
}

/**
 * Remove auth token
 */
function removeAuthToken() {
    localStorage.removeItem('auth_token');
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return !!getAuthToken();
}

/**
 * Get current user from localStorage
 */
function getCurrentUser() {
    const user = localStorage.getItem('current_user');
    return user ? JSON.parse(user) : null;
}

/**
 * Set current user
 */
function setCurrentUser(user) {
    localStorage.setItem('current_user', JSON.stringify(user));
}

/**
 * Update authentication UI across all pages
 */
function updateAuthUI() {
    // Elements that might exist on different pages
    const authLinks = document.getElementById('auth-links');
    const userMenu = document.getElementById('user-menu');
    const authNavLinks = document.getElementById('auth-nav-links');
    const userNavMenu = document.getElementById('user-nav-menu');
    const dashboardLink = document.getElementById('dashboard-link');

    if (isAuthenticated()) {
        // Hide auth links, show user menu
        if (authLinks) authLinks.classList.add('d-none');
        if (userMenu) userMenu.classList.remove('d-none');
        if (authNavLinks) authNavLinks.classList.add('d-none');
        if (userNavMenu) userNavMenu.classList.remove('d-none');
        if (dashboardLink) dashboardLink.style.display = 'block';
    } else {
        // Show auth links, hide user menu
        if (authLinks) authLinks.classList.remove('d-none');
        if (userMenu) userMenu.classList.add('d-none');
        if (authNavLinks) authNavLinks.classList.remove('d-none');
        if (userNavMenu) userNavMenu.classList.add('d-none');
        if (dashboardLink) dashboardLink.style.display = 'none';
    }
}

/**
 * Logout user
 */
async function logout() {
    try {
        if (isAuthenticated()) {
            await apiRequest('/logout', { method: 'POST' });
        }
    } catch (error) {
        console.log('Logout API call failed, proceeding with local logout');
    }

    removeAuthToken();
    localStorage.removeItem('current_user');
    updateAuthUI();
    window.location.href = 'index.html';
}

/**
 * Login user and redirect based on role
 */
async function login(email, password) {
    const response = await apiRequest('/login', {
        method: 'POST',
        body: JSON.stringify({ email, password })
    });

    // Handle response with or without 'data' wrapper
    const token = response.data?.token || response.token;
    const user = response.data?.user || response.user;

    if (!token) {
        throw new Error('No token received from server');
    }

    setAuthToken(token);
    setCurrentUser(user);

    // Redirect based on role
    redirectToDashboard(user?.role);

    return response;
}

/**
 * Redirect user to their appropriate dashboard based on role
 * @param {string} role - User role ('admin', 'creator', 'user')
 */
function redirectToDashboard(role) {
    // Small delay to allow auth state to settle
    setTimeout(() => {
        switch (role) {
            case 'admin':
                window.location.href = 'admin.html';
                break;
            case 'creator':
                window.location.href = 'dashboard.html';
                break;
            case 'user':
            default:
                window.location.href = 'index.html';
                break;
        }
    }, 100);
}

/**
 * Register user - redirects to login page after successful registration
 */
async function register(name, email, password, passwordConfirmation, role = 'user') {
    const response = await apiRequest('/register', {
        method: 'POST',
        body: JSON.stringify({ name, email, password, password_confirmation: passwordConfirmation, role })
    });

    // Handle response with or without 'data' wrapper
    const user = response.data?.user || response.user;

    // Store user info and role for login
    setCurrentUser(user);

    // Redirect to login page after successful registration
    // Small delay to allow the success message to be shown
    setTimeout(() => {
        window.location.href = 'login.html';
    }, 1500);

    return response;
}

/**
 * Redirect user based on their role
 * Call this on page load for dashboard/admin pages
 * to ensure users only access their designated dashboard
 */
function redirectBasedOnRole() {
    if (!isAuthenticated()) {
        // Not logged in, redirect to login
        window.location.href = 'login.html';
        return false;
    }

    const user = getCurrentUser();
    const userRole = user?.role || 'user';

    // Get current page
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';

    // Define redirect rules per role
    const roleRedirects = {
        'admin': {
            'admin.html': true,    // Stay on admin page
            'dashboard.html': false, // Redirect to admin
            'index.html': false,    // Redirect to admin
            'video.html': true,    // Can watch videos
            'profile.html': true   // Can view profile
        },
        'creator': {
            'dashboard.html': true, // Stay on creator dashboard
            'index.html': false,   // Redirect to dashboard
            'admin.html': false,   // No admin access
            'video.html': true,   // Can watch videos
            'profile.html': true, // Can view profile
            'favorites.html': true,
            'history.html': true
        },
        'user': {
            'dashboard.html': true, // Regular dashboard (no upload)
            'index.html': true,     // Stay on home
            'admin.html': false,   // No admin access
            'video.html': true,   // Can watch videos
            'profile.html': true, // Can view profile
            'favorites.html': true,
            'history.html': true
        }
    };

    // Get allowed pages for this role
    const roleAllowed = roleRedirects[userRole] || roleRedirects['user'];

    // Check if current page is allowed
    if (!roleAllowed[currentPage]) {
        // Redirect to appropriate page based on role
        const redirectPage = userRole === 'admin' ? 'admin.html' : 'dashboard.html';
        window.location.href = redirectPage;
        return false;
    }

    return true;
}

/**
 * Check if current page matches user role
 * Returns true if access is allowed, false otherwise
 */
function checkPageAccess() {
    if (!isAuthenticated()) {
        return true; // Let auth check handle this
    }

    const user = getCurrentUser();
    const userRole = user?.role || 'user';
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';

    // Pages that all authenticated users can access
    const universalPages = ['video.html', 'profile.html', 'favorites.html', 'history.html', 'playlist.html', 'search.html', 'category.html'];
    if (universalPages.includes(currentPage)) {
        return true;
    }

    // Role-specific pages
    if (userRole === 'admin' && currentPage === 'admin.html') {
        return true;
    }

    if ((userRole === 'creator' || userRole === 'user') && currentPage === 'dashboard.html') {
        return true;
    }

    if (currentPage === 'index.html') {
        return true;
    }

    // Block access to inappropriate pages
    if (userRole === 'user' && currentPage === 'dashboard.html') {
        // Regular users can access dashboard but without creator features
        return true;
    }

    return false;
}

// ============================================
// API FUNCTIONS
// ============================================

/**
 * Make API request with auth token
 */
async function apiRequest(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const token = getAuthToken();

    const defaultOptions = {
        headers: {
            'Accept': 'application/json',
            ...(token && { 'Authorization': `Bearer ${token}` })
        }
    };

    // Check if body is FormData - don't set Content-Type for FormData
    // Let the browser set it with the correct boundary
    const mergedOptions = { ...defaultOptions, ...options };
    if (options.body instanceof FormData) {
        delete mergedOptions.headers['Content-Type'];
    } else if (!options.headers || !options.headers['Content-Type']) {
        mergedOptions.headers['Content-Type'] = 'application/json';
    }

    try {
        const response = await fetch(url, mergedOptions);
        const text = await response.text();
        console.log('API Raw response text:', endpoint, text.substring(0, 200));

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response text:', text);
            throw new Error('Invalid JSON response');
        }

        console.log('API Parsed data:', endpoint, data);

        if (!response.ok) {
            const errorMessage = data?.message || data?.error || `API request failed (${response.status})`;

            // For validation errors (422), include the errors object in the error
            if (response.status === 422 && data?.errors) {
                const fieldErrors = Object.entries(data.errors)
                    .map(([field, errors]) => `${field}: ${Array.isArray(errors) ? errors.join(', ') : errors}`)
                    .join('; ');
                throw new Error(`${errorMessage} (${fieldErrors})`);
            }

            throw new Error(errorMessage);
        }

        return data;
    } catch (error) {
        console.error('API Request failed:', endpoint, error.message);
        throw error;
    }
}

/**
 * Make multipart/form-data request for file uploads
 */
async function multipartRequest(endpoint, formData, onProgress = null) {
    const url = `${API_BASE_URL}${endpoint}`;
    const token = getAuthToken();

    const options = {
        method: 'POST',
        headers: {
            ...(token && { 'Authorization': `Bearer ${token}` })
        },
        body: formData
    };

    // If progress callback is provided, use XMLHttpRequest
    if (onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url);
            if (token) {
                xhr.setRequestHeader('Authorization', `Bearer ${token}`);
            }

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    onProgress(percentComplete);
                }
            };

            xhr.onload = () => {
                const data = JSON.parse(xhr.responseText);
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(data);
                } else {
                    reject(new Error(data.message || 'Upload failed'));
                }
            };

            xhr.onerror = () => reject(new Error('Network error'));
            xhr.send(formData);
        });
    }

    const response = await fetch(url, options);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.message || 'Request failed');
    }

    return data;
}

// ============================================
// CATEGORY FUNCTIONS
// ============================================

/**
 * Load all categories
 */
async function loadCategories() {
    try {
        const response = await apiRequest('/categories');
        return response.data;
    } catch (error) {
        console.error('Error loading categories:', error);
        return [];
    }
}

/**
 * Render categories grid
 */
function renderCategories(categories, containerId = 'categories-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!categories || categories.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted">No categories available</div>';
        return;
    }

    container.innerHTML = categories.map(category => `
        <div class="col-md-3 mb-3">
            <div class="card category-card h-100" data-category-id="${category.id}">
                <div class="card-body text-center">
                    <h5 class="card-title text-yellow">${escapeHtml(category.name)}</h5>
                    <p class="card-text small">${escapeHtml(category.description || '')}</p>
                    ${category.video_count !== undefined ?
                        `<small class="text-muted">${category.video_count} videos</small>` : ''}
                </div>
            </div>
        </div>
    `).join('');

    // Add click handlers for filtering
    container.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('click', () => {
            const categoryId = card.dataset.categoryId;
            loadVideosByCategory(categoryId);

            // Update active state
            container.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
        });
    });
}

// ============================================
// CHUNKED UPLOAD FUNCTIONS (Performance Optimization)
// ============================================

/**
 * Initialize a chunked upload session
 */
async function initChunkedUpload(file, metadata) {
    const totalChunks = Math.ceil(file.size / CHUNKED_UPLOAD_CONFIG.CHUNK_SIZE);

    const formData = new FormData();
    formData.append('file_name', file.name);
    formData.append('file_size', file.size);
    formData.append('mime_type', file.type || 'video/mp4');
    formData.append('chunk_size', CHUNKED_UPLOAD_CONFIG.CHUNK_SIZE);
    formData.append('total_chunks', totalChunks);
    formData.append('title', metadata.title);
    if (metadata.description) formData.append('description', metadata.description);
    if (metadata.category_id) formData.append('category_id', metadata.category_id);
    if (metadata.thumbnail) formData.append('thumbnail', metadata.thumbnail);

    const response = await apiRequest('/videos/upload/init', {
        method: 'POST',
        body: formData
    });

    return response.data;
}

/**
 * Upload a single chunk with retry logic
 */
async function uploadChunk(sessionId, chunkIndex, chunk, retries = CHUNKED_UPLOAD_CONFIG.MAX_RETRIES) {
    const formData = new FormData();
    formData.append('session_id', sessionId);
    formData.append('chunk_index', chunkIndex);
    formData.append('chunk', chunk);

    for (let attempt = 0; attempt < retries; attempt++) {
        try {
            const response = await apiRequest('/videos/upload/chunk', {
                method: 'POST',
                body: formData
            });
            return response;
        } catch (error) {
            if (attempt === retries - 1) {
                throw error;
            }
            // Wait before retry
            await new Promise(resolve => setTimeout(resolve, CHUNKED_UPLOAD_CONFIG.RETRY_DELAY));
        }
    }
}

/**
 * Upload multiple chunks in parallel
 */
async function uploadChunksParallel(sessionId, chunks, onProgress) {
    const results = [];
    const concurrency = CHUNKED_UPLOAD_CONFIG.PARALLEL_UPLOADS;

    // Process chunks in batches
    for (let i = 0; i < chunks.length; i += concurrency) {
        const batch = chunks.slice(i, i + concurrency);
        const batchResults = await Promise.all(
            batch.map(chunk => uploadChunk(sessionId, chunk.index, chunk.data))
        );
        results.push(...batchResults);

        // Report progress
        if (onProgress) {
            onProgress(results.length, chunks.length);
        }
    }

    return results;
}

/**
 * Complete the chunked upload
 */
async function completeChunkedUpload(sessionId) {
    return await apiRequest('/videos/upload/complete', {
        method: 'POST',
        body: JSON.stringify({ session_id: sessionId })
    });
}

/**
 * Get upload session status
 */
async function getUploadStatus(sessionId) {
    try {
        const response = await apiRequest(`/videos/upload/status/${sessionId}`);
        return response.data;
    } catch (error) {
        return null;
    }
}

/**
 * Abort upload session
 */
async function abortUpload(sessionId) {
    try {
        await apiRequest(`/videos/upload/abort/${sessionId}`, {
            method: 'DELETE'
        });
        return true;
    } catch (error) {
        console.error('Error aborting upload:', error);
        return false;
    }
}

/**
 * Main chunked upload function with progress tracking
 */
async function chunkedUpload(file, metadata, onProgress) {
    // Calculate total chunks
    const chunkSize = CHUNKED_UPLOAD_CONFIG.CHUNK_SIZE;
    const totalChunks = Math.ceil(file.size / chunkSize);

    // Initialize upload session
    const initResult = await initChunkedUpload(file, metadata);
    const { session_id } = initResult;

    // Create file chunks
    const chunks = [];
    for (let i = 0; i < totalChunks; i++) {
        const start = i * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        chunks.push({
            index: i,
            data: file.slice(start, end)
        });
    }

    // Upload chunks with progress tracking
    let uploadedCount = 0;
    const uploadProgressCallback = (completed, total) => {
        uploadedCount = completed;
        if (onProgress) {
            onProgress({
                phase: 'uploading',
                currentChunk: completed,
                totalChunks: total,
                percent: Math.round((completed / total) * 100)
            });
        }
    };

    await uploadChunksParallel(session_id, chunks, uploadProgressCallback);

    // Complete upload
    if (onProgress) {
        onProgress({ phase: 'assembling', percent: 100 });
    }

    const result = await completeChunkedUpload(session_id);

    if (onProgress) {
        onProgress({ phase: 'complete', percent: 100, video: result.data });
    }

    return result;
}

/**
 * Resume interrupted upload
 */
async function resumeUpload(sessionId, file, onProgress) {
    // Get current upload status
    const status = await getUploadStatus(sessionId);

    if (!status || status.status === 'completed') {
        return { message: 'Upload already completed', data: status };
    }

    // Calculate missing chunks
    const chunkSize = CHUNKED_UPLOAD_CONFIG.CHUNK_SIZE;
    const totalChunks = Math.ceil(file.size / chunkSize);

    // Create missing chunks
    const missingChunks = [];
    for (let i = 0; i < totalChunks; i++) {
        if (!status.uploaded_chunk_indices.includes(i)) {
            const start = i * chunkSize;
            const end = Math.min(start + chunkSize, file.size);
            missingChunks.push({
                index: i,
                data: file.slice(start, end)
            });
        }
    }

    if (missingChunks.length > 0) {
        // Upload missing chunks
        let uploadedCount = status.uploaded_chunks;
        const uploadProgressCallback = (completed, total) => {
            uploadedCount = status.uploaded_chunks + completed;
            if (onProgress) {
                onProgress({
                    phase: 'resuming',
                    currentChunk: uploadedCount,
                    totalChunks: totalChunks,
                    percent: Math.round((uploadedCount / totalChunks) * 100)
                });
            }
        };

        await uploadChunksParallel(sessionId, missingChunks, uploadProgressCallback);
    }

    // Complete upload
    const result = await completeChunkedUpload(sessionId);

    if (onProgress) {
        onProgress({ phase: 'complete', percent: 100, video: result.data });
    }

    return result;
}

/**
 * Check and resume any pending uploads from localStorage
 */
async function checkPendingUploads(onProgress) {
    const pendingUploads = JSON.parse(localStorage.getItem('pending_uploads') || '[]');
    const completedUploads = [];

    for (const upload of pendingUploads) {
        try {
            const status = await getUploadStatus(upload.sessionId);

            if (status && status.status !== 'completed' && status.status !== 'failed') {
                // Resume upload
                const file = await getFileFromPending(upload.fileName);
                if (file) {
                    await resumeUpload(upload.sessionId, file, onProgress);
                }
            }

            completedUploads.push(upload.sessionId);
        } catch (error) {
            console.error('Error resuming upload:', upload.sessionId, error);
        }
    }

    // Remove completed from localStorage
    const remaining = pendingUploads.filter(u => !completedUploads.includes(u.sessionId));
    localStorage.setItem('pending_uploads', JSON.stringify(remaining));
}

/**
 * Save upload to localStorage for potential resume
 */
function saveUploadForResume(sessionId, fileName, fileSize) {
    const pendingUploads = JSON.parse(localStorage.getItem('pending_uploads') || '[]');

    // Check if already exists
    if (!pendingUploads.find(u => u.sessionId === sessionId)) {
        pendingUploads.push({
            sessionId,
            fileName,
            fileSize,
            timestamp: Date.now()
        });
        localStorage.setItem('pending_uploads', JSON.stringify(pendingUploads));
    }
}

// ============================================
// SERVER-SIDE VIDEO OPERATIONS
// ============================================

/**
 * List videos available on server storage
 */
async function listServerVideos() {
    try {
        const response = await apiRequest('/videos/server/list');
        return response.data;
    } catch (error) {
        console.error('Error listing server videos:', error);
        return null;
    }
}

/**
 * Import a video from server storage to database
 */
async function importFromServer(filePath, title, description = '', categoryId = null) {
    try {
        const response = await apiRequest('/videos/import-from-server', {
            method: 'POST',
            body: JSON.stringify({
                file_path: filePath,
                title: title,
                description: description,
                category_id: categoryId
            })
        });
        return response.data;
    } catch (error) {
        console.error('Error importing video from server:', error);
        throw error;
    }
}

/**
 * Upload video from server filesystem (copy to storage and create record)
 */
async function uploadFromServer(filePath, title, description = '', categoryId = null, thumbnailFile = null) {
    try {
        const formData = new FormData();
        formData.append('file_path', filePath);
        formData.append('title', title);
        if (description) formData.append('description', description);
        if (categoryId) formData.append('category_id', categoryId);
        if (thumbnailFile) formData.append('thumbnail', thumbnailFile);

        const response = await apiRequest('/videos/upload-from-server', {
            method: 'POST',
            body: formData
        });
        return response.data;
    } catch (error) {
        console.error('Error uploading video from server:', error);
        throw error;
    }
}

/**
 * Get video stream information
 */
async function getVideoStreamInfo(videoId) {
    try {
        const response = await apiRequest(`/videos/${videoId}/stream-info`);
        return response.data;
    } catch (error) {
        console.error('Error getting video stream info:', error);
        return null;
    }
}

/**
 * Load video with full details including stream URL
 * Optimized version that minimizes API calls
 */
async function loadVideoDetails(videoId) {
    try {
        // Load video details - single API call
        const response = await apiRequest(`/videos/${videoId}`);
        const video = response.data;

        if (!video) {
            return null;
        }

        // Directly construct streaming URL - no extra API call needed
        const videoUrl = `${API_BASE_URL}/videos/${videoId}/stream`;

        // Return enhanced video object
        return {
            ...video,
            video_url: videoUrl,
            thumbnail_url: video.thumbnail_url || (video.thumbnail_path ? `${STORAGE_URL}/${video.thumbnail_path}` : null),
            video_file_url: video.video_file_url || (video.file_path ? `${STORAGE_URL}/${video.file_path}` : null)
        };
    } catch (error) {
        console.error('Error loading video details:', error);
        return null;
    }
}

// ============================================
// VIDEO FUNCTIONS
// ============================================

/**
 * Load videos with pagination
 */
async function loadVideos(page = 1, filters = {}) {
    try {
        let endpoint = `/videos?page=${page}`;

        if (filters.category_id) endpoint += `&category_id=${filters.category_id}`;
        if (filters.sort) endpoint += `&sort=${filters.sort}`;
        if (filters.search) endpoint += `&search=${encodeURIComponent(filters.search)}`;

        const response = await apiRequest(endpoint);
        return response.data;
    } catch (error) {
        console.error('Error loading videos:', error);
        return null;
    }
}

/**
 * Load user's own videos
 */
async function loadMyVideos(page = 1) {
    try {
        const response = await apiRequest(`/my-videos?page=${page}`);
        return response.data;
    } catch (error) {
        console.error('Error loading my videos:', error);
        return null;
    }
}

/**
 * Load ALL videos on the platform (for debugging/admin purposes)
 * This helps when videos are uploaded manually via phpMyAdmin
 */
async function loadAllVideos(page = 1, filters = {}) {
    try {
        let endpoint = `/videos/all?page=${page}`;

        if (filters.category_id) endpoint += `&category_id=${filters.category_id}`;
        if (filters.sort) endpoint += `&sort=${filters.sort}`;
        if (filters.status) endpoint += `&status=${filters.status}`;

        const response = await apiRequest(endpoint);
        return response.data;
    } catch (error) {
        console.error('Error loading all videos:', error);
        return null;
    }
}

/**
 * Load video details
 */
async function loadVideo(videoId) {
    try {
        const response = await apiRequest(`/videos/${videoId}`);
        return response.data;
    } catch (error) {
        console.error('Error loading video:', error);
        return null;
    }
}

/**
 * Search videos by title only (for quick autocomplete)
 * @param {string} query - Search query
 * @param {number} limit - Maximum results to return
 * @returns {Promise<array>}
 */
async function searchVideosByName(query, limit = 10) {
    try {
        const response = await apiRequest(`/videos/search/name?q=${encodeURIComponent(query)}&limit=${limit}`);
        return response.data || [];
    } catch (error) {
        console.error('Error searching videos by name:', error);
        return [];
    }
}

/**
 * Search videos (full search with pagination)
 * @param {string} query - Search query
 * @param {number} page - Page number
 * @returns {Promise<array>}
 */
async function searchVideos(query, page = 1) {
    try {
        const response = await apiRequest(`/videos/search?q=${encodeURIComponent(query)}&page=${page}`);
        return response.data;
    } catch (error) {
        console.error('Error searching videos:', error);
        return [];
    }
}

/**
 * Load videos by category
 * @param {number|string} categoryId - Category ID, slug, or name
 * @param {number} page - Page number
 * @returns {Promise<object|null>}
 */
async function loadVideosByCategory(categoryId, page = 1) {
    try {
        const response = await apiRequest(`/videos/category/${categoryId}?page=${page}`);
        return response;
    } catch (error) {
        console.error('Error loading videos by category:', error);
        return null;
    }
}

/**
 * Render video cards
 */
function renderVideos(videos, containerId = 'videos-container', clearContainer = true) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (clearContainer) {
        container.innerHTML = '';
    }

    if (!videos || videos.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted">No videos found</div>';
        return;
    }

    container.innerHTML += videos.map(video => createVideoCard(video)).join('');
}

/**
 * Create video card HTML
 */
function createVideoCard(video, showActions = false) {
    // Use thumbnail_url from backend if available, otherwise use helper function
    const thumbnail = getVideoThumbnail(video);

    // Use video_url from backend for streaming if available, otherwise construct from video.id
    const videoLink = video.video_url
        ? `video.html?url=${encodeURIComponent(video.video_url)}&id=${video.id}`
        : `video.html?id=${video.id}`;

    const userName = video.user?.name || 'Unknown User';
    const categoryName = video.category?.name || 'Uncategorized';
    const views = formatNumber(video.views_count || 0);
    const duration = formatDuration(video.duration);
    const visibilityBadge = getVisibilityBadge(video.visibility);

    let actionsHtml = '';
    if (showActions) {
        actionsHtml = `
            <div class="video-actions mt-2">
                <a href="video.html?id=${video.id}" class="btn btn-sm btn-danger">Watch</a>
                <button class="btn btn-sm btn-outline-light" onclick="editVideo(${video.id})">Edit</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteVideo(${video.id})">Delete</button>
            </div>
        `;
    }

    return `
        <div class="col-md-4 video-card">
            <div class="card h-100">
                <a href="${videoLink}">
                    <img src="${thumbnail}" class="card-img-top video-thumbnail" alt="${escapeHtml(video.title)}">
                </a>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title video-title">
                        <a href="${videoLink}" class="text-decoration-none text-white">
                            ${escapeHtml(video.title)}
                        </a>
                    </h5>
                    <p class="card-text flex-grow-1 small text-muted">
                        ${video.description ? escapeHtml(video.description.substring(0, 80)) + '...' : 'No description'}
                    </p>
                    <div class="video-meta small d-flex flex-wrap align-items-center gap-2">
                        <span>${views} views</span>
                        <span>${userName}</span>
                        <span class="badge bg-secondary">${categoryName}</span>
                        ${visibilityBadge}
                    </div>
                    <div class="video-duration small text-yellow mt-1">
                        ${duration}
                    </div>
                    ${actionsHtml}
                </div>
            </div>
        </div>
    `;
}

/**
 * Update load more button visibility
 */
function updateLoadMoreButton(data, currentPage, filters = {}) {
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (!loadMoreBtn) return;

    // Check if there are more pages
    const hasMorePages = data.links?.next || (data.meta?.current_page < data.meta?.last_page);

    if (hasMorePages) {
        loadMoreBtn.style.display = 'block';
        loadMoreBtn.onclick = () => {
            const nextPage = (data.meta?.current_page || currentPage) + 1;
            loadVideos(nextPage, filters).then(newData => {
                if (newData) {
                    renderVideos(newData.data || newData.videos || newData, 'videos-container', false);
                    updateLoadMoreButton(newData, nextPage, filters);
                }
            });
        };
    } else {
        loadMoreBtn.style.display = 'none';
    }
}

/**
 * Upload video
 */
async function uploadVideo(formData, onProgress) {
    return await multipartRequest('/videos', formData, onProgress);
}

/**
 * Delete video
 */
async function deleteVideo(videoId) {
    if (!confirm('Are you sure you want to delete this video?')) return;

    try {
        await apiRequest(`/videos/${videoId}`, { method: 'DELETE' });
        showAlert('Video deleted successfully', 'success');
        // Remove from DOM
        const videoCard = document.querySelector(`[data-video-id="${videoId}"]`);
        if (videoCard) {
            videoCard.closest('.video-card').remove();
        }
    } catch (error) {
        showAlert('Failed to delete video: ' + error.message, 'danger');
    }
}

// ============================================
// VIDEO EDIT FUNCTIONS
// ============================================

/**
 * Get video data for editing
 * @param {number} videoId - The video ID
 * @returns {Promise<object|null>}
 */
async function getVideoForEdit(videoId) {
    try {
        const response = await apiRequest(`/videos/${videoId}/edit`);
        return response.data;
    } catch (error) {
        console.error('Error getting video for edit:', error);
        showAlert('Failed to load video data for editing', 'danger');
        return null;
    }
}

/**
 * Update a video with new data
 * @param {number} videoId - The video ID
 * @param {object} data - Video data to update (title, description, category_id, visibility)
 * @param {File|null} thumbnail - Optional new thumbnail file
 * @returns {Promise<object>}
 */
async function updateVideo(videoId, data, thumbnail = null) {
    try {
        // Determine content type based on whether we have a file
        let response;
        if (thumbnail) {
            // Use FormData for file upload
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('title', data.title);
            if (data.description !== undefined) formData.append('description', data.description);
            if (data.category_id) formData.append('category_id', data.category_id);
            if (data.visibility) formData.append('visibility', data.visibility);
            formData.append('thumbnail', thumbnail);

            response = await apiRequest(`/videos/${videoId}`, {
                method: 'POST', // Laravel simulates PUT with _method
                body: formData
            });
        } else {
            // Use JSON for regular updates
            response = await apiRequest(`/videos/${videoId}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        }
        return response;
    } catch (error) {
        console.error('Error updating video:', error);
        throw error;
    }
}

/**
 * Update video visibility
 * @param {number} videoId - The video ID
 * @param {string} visibility - New visibility (public, private, unlisted)
 * @returns {Promise<object>}
 */
async function updateVideoVisibility(videoId, visibility) {
    try {
        const response = await apiRequest(`/videos/${videoId}`, {
            method: 'PUT',
            body: JSON.stringify({ visibility })
        });
        return response;
    } catch (error) {
        console.error('Error updating video visibility:', error);
        throw error;
    }
}

/**
 * Get visibility badge HTML
 * @param {string} visibility - Video visibility
 * @param {string} label - Optional custom label
 * @returns {string} HTML for visibility badge
 */
function getVisibilityBadge(visibility, label = null) {
    const badges = {
        'public': '<span class="badge bg-success"><i class="fas fa-globe me-1"></i>Public</span>',
        'private': '<span class="badge bg-warning text-dark"><i class="fas fa-lock me-1"></i>Private</span>',
        'unlisted': '<span class="badge bg-secondary"><i class="fas fa-link me-1"></i>Unlisted</span>'
    };
    return badges[visibility] || badges['public'];
}

/**
 * Open video edit modal with video data
 * @param {number} videoId - The video ID to edit
 */
async function editVideo(videoId) {
    try {
        // Show loading state
        showAlert('Loading video data...', 'info');

        const video = await getVideoForEdit(videoId);

        if (!video) {
            return;
        }

        // Create or get edit modal
        let modal = document.getElementById('editVideoModal');
        if (!modal) {
            modal = createEditVideoModal();
            document.body.appendChild(modal);
        }

        // Populate form with video data
        document.getElementById('edit-video-id').value = video.id;
        document.getElementById('edit-video-title').value = video.title || '';
        document.getElementById('edit-video-description').value = video.description || '';
        document.getElementById('edit-video-category').value = video.category_id || '';
        document.getElementById('edit-video-visibility').value = video.visibility || 'public';

        // Show current thumbnail
        const thumbnailPreview = document.getElementById('edit-thumbnail-preview');
        if (video.thumbnail_path) {
            thumbnailPreview.src = `${STORAGE_URL}/${video.thumbnail_path}`;
            thumbnailPreview.style.display = 'block';
        } else {
            thumbnailPreview.style.display = 'none';
        }

        // Clear thumbnail input
        document.getElementById('edit-video-thumbnail').value = '';

        // Populate category select
        await populateCategorySelect('edit-video-category');

        // Set category value again after populating
        document.getElementById('edit-video-category').value = video.category_id || '';

        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    } catch (error) {
        showAlert('Error opening editor: ' + error.message, 'danger');
    }
}

/**
 * Create the edit video modal HTML
 * @returns {HTMLElement}
 */
function createEditVideoModal() {
    const modalDiv = document.createElement('div');
    modalDiv.id = 'editVideoModal';
    modalDiv.className = 'modal fade';
    modalDiv.tabIndex = '-1';
    modalDiv.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title text-yellow">Edit Video</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-video-form">
                        <input type="hidden" id="edit-video-id">

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="edit-video-title" class="form-label">Video Title *</label>
                                    <input type="text" class="form-control" id="edit-video-title" required
                                           placeholder="Enter video title" maxlength="255">
                                </div>
                                <div class="mb-3">
                                    <label for="edit-video-description" class="form-label">Description</label>
                                    <textarea class="form-control" id="edit-video-description" rows="4"
                                              placeholder="Describe your video"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-video-category" class="form-label">Category</label>
                                    <select class="form-control" id="edit-video-category">
                                        <option value="">Select Category</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-video-visibility" class="form-label">Visibility</label>
                                    <select class="form-control" id="edit-video-visibility">
                                        <option value="public">Public - Everyone can see</option>
                                        <option value="unlisted">Unlisted - Only people with link</option>
                                        <option value="private">Private - Only you can see</option>
                                    </select>
                                    <small class="text-muted">Choose who can watch this video</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit-video-thumbnail" class="form-label">Thumbnail (Optional)</label>
                                    <input type="file" class="form-control" id="edit-video-thumbnail" accept="image/*">
                                    <small class="text-muted">Recommended: 1280x720 JPG or PNG</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Current Thumbnail</label>
                                    <div class="thumbnail-container">
                                        <img id="edit-thumbnail-preview" src="" alt="Current thumbnail"
                                             class="img-thumbnail" style="display: none; max-width: 100%;">
                                        <p id="edit-thumbnail-placeholder" class="text-muted text-center py-4 border rounded">
                                            No thumbnail
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="save-edit-video-btn">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    `;

    // Add event listener for save button
    const saveBtn = modalDiv.querySelector('#save-edit-video-btn');
    saveBtn.addEventListener('click', handleVideoEditSubmit);

    // Add thumbnail preview
    const thumbnailInput = modalDiv.querySelector('#edit-video-thumbnail');
    thumbnailInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('edit-thumbnail-preview');
        const placeholder = document.getElementById('edit-thumbnail-placeholder');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
            placeholder.style.display = 'block';
        }
    });

    return modalDiv;
}

/**
 * Handle video edit form submission
 */
async function handleVideoEditSubmit() {
    const videoId = document.getElementById('edit-video-id').value;
    const title = document.getElementById('edit-video-title').value.trim();
    const description = document.getElementById('edit-video-description').value.trim();
    const categoryId = document.getElementById('edit-video-category').value;
    const visibility = document.getElementById('edit-video-visibility')?.value || 'public';
    const thumbnailFile = document.getElementById('edit-video-thumbnail').files[0];

    if (!title) {
        showAlert('Please enter a video title', 'danger');
        return;
    }

    const saveBtn = document.getElementById('save-edit-video-btn');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="loading"></span> Saving...';

    try {
        const data = {
            title: title,
            description: description,
            category_id: categoryId || null,
            visibility: visibility
        };

        await updateVideo(videoId, data, thumbnailFile || null);

        showAlert('Video updated successfully!', 'success');

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('editVideoModal'));
        modal.hide();

        // Refresh video lists if on dashboard
        if (typeof loadMyVideosList === 'function') {
            loadMyVideosList();
        }

        // Reload current page if on video.html
        if (window.location.pathname.includes('video.html')) {
            window.location.reload();
        }
    } catch (error) {
        showAlert('Failed to update video: ' + error.message, 'danger');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}

// ============================================
// PLAYLIST FUNCTIONS
// ============================================

/**
 * Load user playlists
 */
async function loadPlaylists() {
    try {
        const response = await apiRequest('/playlists');
        // Handle paginated response - API returns {data: {data: [...], meta: {...}}}
        // We need to return the actual array of playlists
        return response.data?.data || response.data || [];
    } catch (error) {
        console.error('Error loading playlists:', error);
        return [];
    }
}

/**
 * Create playlist
 */
async function createPlaylist(name, description, isPublic = false) {
    return await apiRequest('/playlists', {
        method: 'POST',
        body: JSON.stringify({ name, description, is_public: isPublic })
    });
}

/**
 * Update playlist
 */
async function updatePlaylist(playlistId, data) {
    return await apiRequest(`/playlists/${playlistId}`, {
        method: 'PUT',
        body: JSON.stringify(data)
    });
}

/**
 * Delete playlist
 */
async function deletePlaylist(playlistId) {
    if (!confirm('Are you sure you want to delete this playlist?')) return;

    try {
        await apiRequest(`/playlists/${playlistId}`, { method: 'DELETE' });
        showAlert('Playlist deleted successfully', 'success');
        return true;
    } catch (error) {
        showAlert('Failed to delete playlist: ' + error.message, 'danger');
        return false;
    }
}

/**
 * Add video to playlist
 */
async function addVideoToPlaylist(playlistId, videoId) {
    try {
        const response = await apiRequest(`/playlists/${playlistId}/videos`, {
            method: 'POST',
            body: JSON.stringify({ video_id: videoId })
        });
        showAlert('Video added to playlist', 'success');
        return response;
    } catch (error) {
        showAlert('Failed to add video: ' + error.message, 'danger');
        throw error;
    }
}

/**
 * Remove video from playlist
 */
async function removeVideoFromPlaylist(playlistId, videoId) {
    try {
        await apiRequest(`/playlists/${playlistId}/videos/${videoId}`, { method: 'DELETE' });
        showAlert('Video removed from playlist', 'success');
    } catch (error) {
        showAlert('Failed to remove video: ' + error.message, 'danger');
    }
}

/**
 * Render playlists
 */
function renderPlaylists(playlists, containerId = 'playlists-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!playlists || playlists.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted">No playlists yet</div>';
        return;
    }

    container.innerHTML = playlists.map(playlist => `
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title text-yellow">${escapeHtml(playlist.name)}</h5>
                    <p class="card-text small">${escapeHtml(playlist.description || 'No description')}</p>
                    <small class="text-muted">${playlist.videos_count || 0} videos</small>
                    <span class="badge ${playlist.is_public ? 'bg-success' : 'bg-secondary'} ms-2">
                        ${playlist.is_public ? 'Public' : 'Private'}
                    </span>
                </div>
                <div class="card-footer">
                    <a href="playlist.html?id=${playlist.id}" class="btn btn-sm btn-danger">View</a>
                    <button class="btn btn-sm btn-outline-danger" onclick="deletePlaylist(${playlist.id})">Delete</button>
                </div>
            </div>
        </div>
    `).join('');
}

// ============================================
// WATCH HISTORY FUNCTIONS
// ============================================

/**
 * Load watch history
 */
async function loadWatchHistory(page = 1) {
    try {
        const response = await apiRequest(`/history?page=${page}`);
        return response.data;
    } catch (error) {
        console.error('Error loading watch history:', error);
        return null;
    }
}

/**
 * Load continue watching videos
 */
async function loadContinueWatching() {
    try {
        const response = await apiRequest('/history/continue-watching');
        return response.data;
    } catch (error) {
        console.error('Error loading continue watching:', error);
        return [];
    }
}

/**
 * Record watch progress
 * Optimized to minimize API calls - only records every 10 seconds
 */
let lastRecordedProgress = 0;
async function recordWatchProgress(videoId, progress, completed = false) {
    // Only record every 10 seconds or on completion to reduce API calls
    if (completed || progress - lastRecordedProgress >= 10) {
        try {
            await apiRequest(`/videos/${videoId}/watch`, {
                method: 'POST',
                body: JSON.stringify({ progress, completed })
            });
            lastRecordedProgress = progress;
        } catch (error) {
            console.error('Error recording watch progress:', error);
        }
    }
}

/**
 * Get video watch history
 */
async function getVideoHistory(videoId) {
    try {
        const response = await apiRequest(`/history/video/${videoId}`);
        return response.data;
    } catch (error) {
        return null;
    }
}

/**
 * Render watch history
 */
function renderWatchHistory(history, containerId = 'continue-watching-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!history || history.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted">No watch history</div>';
        return;
    }

    container.innerHTML = history.map(item => `
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <a href="video.html?id=${item.video.id}">
                    <img src="${getVideoThumbnail(item.video)}"
                         class="card-img-top video-thumbnail" alt="${escapeHtml(item.video.title)}">
                </a>
                <div class="card-body">
                    <h6 class="card-title">${escapeHtml(item.video.title)}</h6>
                    <small class="text-muted">${formatDuration(item.progress)} / ${formatDuration(item.video.duration)}</small>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-danger" role="progressbar"
                             style="width: ${Math.min((item.progress / item.video.duration) * 100, 100)}%"></div>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// ============================================
// VIDEO PLAYER FUNCTIONS
// ============================================

/**
 * Initialize video player with streaming
 * Optimized with reduced timeupdate frequency
 * @param {number} videoId - The video ID
 * @param {string} [videoUrl] - Optional direct video URL (from backend video_url accessor)
 */
function initializeVideoPlayer(videoId, videoUrl = null) {
    const video = document.getElementById('video-player');
    if (!video) return;

    // Use the provided video_url if available, otherwise construct streaming URL
    const streamUrl = videoUrl || `${API_BASE_URL}/videos/${videoId}/stream`;

    video.src = streamUrl;
    video.preload = 'metadata'; // Only load metadata initially for faster startup

    // Track watch progress - reduced frequency for performance
    let lastProgressTime = 0;
    video.addEventListener('timeupdate', () => {
        const progress = Math.floor(video.currentTime);
        const duration = video.duration;

        // Record progress every 10 seconds (reduced from 5)
        if (progress > 0 && (progress - lastProgressTime >= 10 || video.ended)) {
            recordWatchProgress(videoId, progress, video.ended);
            lastProgressTime = progress;
        }

        // Update progress bar if exists (throttled)
        const progressBar = document.getElementById('watch-progress');
        if (progressBar && duration) {
            progressBar.style.width = `${(progress / duration) * 100}%`;
        }
    });

    // Mark as completed when video ends
    video.addEventListener('ended', () => {
        recordWatchProgress(videoId, Math.floor(video.duration), true);
        showAlert('Video completed!', 'success');
    });

    // Restore watch position if available
    getVideoHistory(videoId).then(history => {
        if (history && history.progress > 0) {
            const continueBtn = document.getElementById('continue-btn');
            if (continueBtn) {
                continueBtn.style.display = 'block';
                continueBtn.textContent = `Continue from ${formatDuration(history.progress)}`;
                continueBtn.onclick = () => {
                    video.currentTime = history.progress;
                    video.play();
                };
            }
        }
    });
}

/**
 * Render related videos
 */
function renderRelatedVideos(videos, currentVideoId) {
    const container = document.getElementById('related-videos-container');
    if (!container) return;

    // Filter out current video
    const relatedVideos = videos.filter(v => v.id !== currentVideoId).slice(0, 4);

    if (relatedVideos.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted">No related videos</div>';
        return;
    }

    container.innerHTML = relatedVideos.map(video => `
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <a href="video.html?id=${video.id}">
                    <img src="${getVideoThumbnail(video)}"
                         class="card-img-top video-thumbnail" alt="${escapeHtml(video.title)}">
                </a>
                <div class="card-body">
                    <h6 class="card-title">${escapeHtml(video.title)}</h6>
                    <small class="text-muted">${formatNumber(video.views_count || 0)} views</small>
                </div>
            </div>
        </div>
    `).join('');
}

// ============================================
// LIKE FUNCTIONS
// ============================================

/**
 * Toggle like status for a video
 * @param {number} videoId - The video ID
 * @returns {Promise<object>} Like status response
 */
async function toggleLike(videoId) {
    try {
        const response = await apiRequest(`/videos/${videoId}/like`, {
            method: 'POST'
        });
        console.log('toggleLike success:', response);
        return response;
    } catch (error) {
        console.error('toggleLike error:', error.message);
        console.error('Auth token:', getAuthToken());
        console.error('Is authenticated:', isAuthenticated());
        throw error;
    }
}

/**
 * Get like status for a video (likes count and user like status)
 * @param {number} videoId - The video ID
 * @returns {Promise<object>} Like status with count and is_liked
 */
async function getLikeStatus(videoId) {
    try {
        const response = await apiRequest(`/videos/${videoId}/like/status`);

        // Debug logging
        console.log('Like status response:', response);

        // Handle response with or without 'data' wrapper
        if (response && response.data) {
            return response.data;
        } else if (response && typeof response === 'object') {
            // Response might be the data directly
            return response;
        }

        // Fallback
        console.warn('Unexpected like status response format:', response);
        return { likes_count: 0, is_liked: false, is_authenticated: false };
    } catch (error) {
        console.error('Error getting like status:', error);
        return { likes_count: 0, is_liked: false, is_authenticated: false };
    }
}

/**
 * Load all videos liked by the authenticated user
 * @param {number} page - Page number for pagination
 * @returns {Promise<object>} Paginated liked videos
 */
async function loadLikedVideos(page = 1) {
    try {
        const response = await apiRequest(`/user/liked-videos?page=${page}`);
        return response;
    } catch (error) {
        console.error('Error loading liked videos:', error);
        return { data: [], meta: { total: 0, current_page: 1, last_page: 1 } };
    }
}

/**
 * Update like button UI based on current status
 * @param {number} videoId - The video ID
 * @param {HTMLElement} button - The like button element
 */
async function updateLikeButton(videoId, button) {
    if (!button) return;

    try {
        const status = await getLikeStatus(videoId);

        const likeIcon = document.getElementById('like-icon');
        const countSpan = button.querySelector('.likes-count');

        if (status.is_liked) {
            button.classList.add('liked');
            if (likeIcon) {
                likeIcon.textContent = '❤️';
            }
            button.style.color = 'var(--primary-red)';
        } else {
            button.classList.remove('liked');
            if (likeIcon) {
                likeIcon.textContent = '♡';
            }
            button.style.color = '';
        }

        if (countSpan) {
            countSpan.textContent = formatNumber(status.likes_count || 0);
        }
    } catch (error) {
        console.error('Error updating like button:', error);
    }
}

/**
 * Initialize like button on video page
 * @param {number} videoId - The video ID
 */
async function initLikeButton(videoId) {
    const likeBtn = document.getElementById('like-btn');
    if (!likeBtn) return;

    // Load initial like status (only if logged in)
    if (isAuthenticated()) {
        await updateLikeButton(videoId, likeBtn);
    }

    // Add click handler (works for both logged in and logged out users)
    likeBtn.addEventListener('click', async function() {
        if (!isAuthenticated()) {
            showAlert('Please login to like videos', 'warning');
            return;
        }

        try {
            // Disable button during request to prevent double-clicks
            const originalDisabled = likeBtn.disabled;
            likeBtn.disabled = true;

            const response = await toggleLike(videoId);

            // Debug logging for toggle response
            console.log('Toggle like response:', response);

            // Handle response - apiRequest returns parsed JSON
            // Response format: { message: "...", data: { liked: bool, likes_count: int } }
            // Or directly: { liked: bool, likes_count: int }
            let result = response;
            if (response && typeof response === 'object' && response.data) {
                result = response.data;
            }

            console.log('Parsed like result:', result);

            // If result is still not valid, refresh the like status from server
            if (!result || typeof result !== 'object') {
                console.warn('Invalid like response, refreshing status');
                await updateLikeButton(videoId, likeBtn);
                likeBtn.disabled = originalDisabled;
                return;
            }

            // Update like button UI
            const likeIcon = document.getElementById('like-icon');
            const countSpan = likeBtn.querySelector('.likes-count');

            if (result.liked) {
                likeBtn.classList.add('liked');
                if (likeIcon) {
                    likeIcon.textContent = '❤️';
                }
                likeBtn.style.color = 'var(--primary-red)';
                showToast('Video liked!', 'success');
            } else {
                likeBtn.classList.remove('liked');
                if (likeIcon) {
                    likeIcon.textContent = '♡';
                }
                likeBtn.style.color = '';
                showToast('Removed from likes', 'info');
            }

            if (countSpan && result.likes_count !== undefined) {
                countSpan.textContent = formatNumber(result.likes_count);
            }

            // Re-enable button
            likeBtn.disabled = originalDisabled;
        } catch (error) {
            console.error('Error toggling like:', error);
            showAlert('Failed to update like: ' + (error.message || 'Unknown error'), 'danger');
            // Re-enable button on error
            likeBtn.disabled = false;
        }
    });
}

// ============================================
// PLAYLIST MANAGEMENT
// ============================================

/**
 * Load playlist with videos
 */
async function loadPlaylist(playlistId) {
    try {
        const response = await apiRequest(`/playlists/${playlistId}`);
        return response.data;
    } catch (error) {
        console.error('Error loading playlist:', error);
        return null;
    }
}

/**
 * Render playlist videos
 */
function renderPlaylistVideos(playlist, containerId = 'playlist-videos-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!playlist.videos || playlist.videos.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted">No videos in this playlist</div>';
        return;
    }

    container.innerHTML = playlist.videos.map((item, index) => `
        <div class="playlist-video-item d-flex align-items-center mb-2 p-2 bg-dark rounded" data-position="${item.position}">
            <span class="me-3 text-muted">${item.position + 1}</span>
            <img src="${getVideoThumbnail(item.video)}"
                 class="me-3" style="width: 120px; height: 68px; object-fit: cover;">
            <div class="flex-grow-1">
                <a href="video.html?id=${item.video.id}" class="text-decoration-none text-white">
                    ${escapeHtml(item.video.title)}
                </a>
                <br>
                <small class="text-muted">${item.video.user?.name}</small>
            </div>
            <button class="btn btn-sm btn-outline-danger" onclick="removeFromPlaylist(${playlist.id}, ${item.video.id})">
                Remove
            </button>
        </div>
    `).join('');
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Get video thumbnail URL with fallback
 * Uses thumbnail_url from backend if available, otherwise constructs from thumbnail_path
 * @param {object} video - Video object with thumbnail_url or thumbnail_path
 * @returns {string} Thumbnail URL
 */
function getVideoThumbnail(video) {
    if (video.thumbnail_url) {
        return video.thumbnail_url;
    }
    if (video.thumbnail_path) {
        return `${STORAGE_URL}/${video.thumbnail_path}`;
    }
    return 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200" viewBox="0 0 400 200"><rect fill="%23333" width="400" height="200"/><text fill="%23666" font-family="Arial" font-size="20" x="50%" y="50%" text-anchor="middle">No Thumbnail</text></svg>';
}

/**
 * Populate category select dropdown
 */
async function populateCategorySelect(selectId = 'video-category') {
    const select = document.getElementById(selectId);
    if (!select) return;

    const categories = await loadCategories();
    select.innerHTML = '<option value="">Select Category</option>';

    categories.forEach(category => {
        select.innerHTML += `<option value="${category.id}">${escapeHtml(category.name)}</option>`;
    });
}

/**
 * Populate playlist select dropdown
 */
async function populatePlaylistSelect(selectId = 'add-to-playlist') {
    const select = document.getElementById(selectId);
    if (!select) return;

    const playlists = await loadPlaylists();
    select.innerHTML = '<option value="">Add to Playlist</option>';

    playlists.forEach(playlist => {
        select.innerHTML += `<option value="${playlist.id}">${escapeHtml(playlist.name)}</option>`;
    });
}

// ============================================
// INITIALIZATION
// ============================================

/**
 * Initialize the application
 */
function initializeApp() {
    updateAuthUI();

    // Add logout handlers
    document.addEventListener('click', function(e) {
        if (e.target.id === 'logout-btn' || e.target.id === 'video-logout-btn') {
            e.preventDefault();
            logout();
        }
    });

    // Load categories on index page
    if (document.getElementById('categories-container')) {
        loadCategories().then(categories => {
            renderCategories(categories);
        });
    }

    // Load videos on index page
    if (document.getElementById('videos-container')) {
        loadVideos().then(data => {
            if (data) {
                renderVideos(data.data || data, 'videos-container', true);
                updateLoadMoreButton(data, 1);
            }
        });
    }

    // Setup search
    const searchBtn = document.getElementById('search-btn');
    const searchInput = document.getElementById('search-input');

    if (searchBtn && searchInput) {
        const performSearch = () => {
            const query = searchInput.value.trim();
            if (query) {
                searchVideos(query).then(videos => {
                    renderVideos(videos, 'videos-container', true);
                    const loadMoreBtn = document.getElementById('load-more-btn');
                    if (loadMoreBtn) loadMoreBtn.style.display = 'none';
                });
            }
        };

        searchBtn.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') performSearch();
        });
    }
}

// Run on DOM ready
document.addEventListener('DOMContentLoaded', initializeApp);

// Export functions for use in inline scripts
window.showAlert = showAlert;
window.formatDuration = formatDuration;
window.formatNumber = formatNumber;
window.formatDate = formatDate;
window.escapeHtml = escapeHtml;
window.loadCategories = loadCategories;
window.loadVideos = loadVideos;
window.loadMyVideos = loadMyVideos;
window.loadAllVideos = loadAllVideos;
window.loadVideo = loadVideo;
window.renderVideos = renderVideos;
window.createVideoCard = createVideoCard;
window.uploadVideo = uploadVideo;
window.deleteVideo = deleteVideo;
window.loadPlaylists = loadPlaylists;
window.createPlaylist = createPlaylist;
window.deletePlaylist = deletePlaylist;
window.addVideoToPlaylist = addVideoToPlaylist;
window.removeVideoFromPlaylist = removeVideoFromPlaylist;
window.renderPlaylists = renderPlaylists;
window.loadWatchHistory = loadWatchHistory;
window.loadContinueWatching = loadContinueWatching;
window.renderWatchHistory = renderWatchHistory;
window.recordWatchProgress = recordWatchProgress;
window.initializeVideoPlayer = initializeVideoPlayer;
window.renderRelatedVideos = renderRelatedVideos;
window.populateCategorySelect = populateCategorySelect;
window.populatePlaylistSelect = populatePlaylistSelect;
window.getAuthToken = getAuthToken;
window.setAuthToken = setAuthToken;
window.removeAuthToken = removeAuthToken;
window.isAuthenticated = isAuthenticated;
window.logout = logout;
window.login = login;
window.register = register;
window.getCurrentUser = getCurrentUser;
window.setCurrentUser = setCurrentUser;
window.apiRequest = apiRequest;
window.STORAGE_URL = STORAGE_URL;
window.formatTimeAgo = formatTimeAgo;
window.showToast = showToast;
window.editVideo = editVideo;
window.updateVideo = updateVideo;
window.getVideoForEdit = getVideoForEdit;
window.getVideoThumbnail = getVideoThumbnail;
window.toggleLike = toggleLike;
window.getLikeStatus = getLikeStatus;
window.loadLikedVideos = loadLikedVideos;
window.updateLikeButton = updateLikeButton;
window.initLikeButton = initLikeButton;
window.loadVideosByCategory = loadVideosByCategory;
window.searchVideosByName = searchVideosByName;

// ============================================
// ROLE DETECTION FUNCTIONS
// ============================================

/**
 * Get current user's role
 * @returns {string|null} User role ('admin', 'creator', 'user') or null if not logged in
 */
function getUserRole() {
    const user = getCurrentUser();
    return user?.role || 'user';
}

/**
 * Check if current user is a creator
 * @returns {boolean}
 */
function isCreator() {
    const role = getUserRole();
    return role === 'creator';
}

/**
 * Check if current user is an admin
 * @returns {boolean}
 */
function isAdmin() {
    const role = getUserRole();
    return role === 'admin';
}

/**
 * Check if current user is a regular viewer (non-creator)
 * @returns {boolean}
 */
function isViewer() {
    const role = getUserRole();
    return role === 'user';
}

/**
 * Show/hide elements based on user role
 * Uses CSS classes: 'creator-only', 'admin-only', 'user-only', 'viewer-only'
 */
function applyRoleBasedVisibility() {
    const role = getUserRole();

    // Show/hide creator-only elements
    document.querySelectorAll('.creator-only').forEach(el => {
        el.style.display = (role === 'creator' || role === 'admin') ? '' : 'none';
    });

    // Show/hide admin-only elements
    document.querySelectorAll('.admin-only').forEach(el => {
        el.style.display = (role === 'admin') ? '' : 'none';
    });

    // Show/hide user-only elements (visible to all logged in users)
    document.querySelectorAll('.user-only').forEach(el => {
        el.style.display = (role === 'user' || role === 'creator' || role === 'admin') ? '' : 'none';
    });

    // Show/hide viewer-only elements (visible only to non-creators)
    document.querySelectorAll('.viewer-only').forEach(el => {
        el.style.display = (role === 'user') ? '' : 'none';
    });
}

/**
 * Update dashboard UI based on user role
 * @param {string} role - User role
 */
function updateDashboardForRole(role) {
    const user = getCurrentUser();

    // Update user name display
    const userNameEl = document.getElementById('user-name');
    if (userNameEl) {
        userNameEl.textContent = user?.name || 'User';
    }

    // Update role indicator in navbar or welcome message
    const roleIndicator = document.getElementById('role-indicator');
    if (roleIndicator) {
        if (role === 'creator') {
            roleIndicator.innerHTML = '<span class="badge bg-purple text-white"><i class="fas fa-video me-1"></i>Creator</span>';
        } else if (role === 'admin') {
            roleIndicator.innerHTML = '<span class="badge bg-danger"><i class="fas fa-crown me-1"></i>Admin</span>';
        } else {
            roleIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-user me-1"></i>Viewer</span>';
        }
    }

    // Show/hide creator features
    const uploadSection = document.getElementById('upload-section');
    const creatorAnalytics = document.getElementById('creator-analytics');
    const serverVideosSection = document.getElementById('server-videos-section');

    if (uploadSection) {
        uploadSection.style.display = (role === 'creator' || role === 'admin') ? 'block' : 'none';
    }

    if (creatorAnalytics) {
        creatorAnalytics.style.display = (role === 'creator' || role === 'admin') ? 'block' : 'none';
    }

    if (serverVideosSection) {
        serverVideosSection.style.display = (role === 'creator' || role === 'admin') ? 'block' : 'none';
    }

    // Apply general role-based visibility
    applyRoleBasedVisibility();
}

// Export role functions globally
window.getUserRole = getUserRole;
window.isCreator = isCreator;
window.isAdmin = isAdmin;
window.isViewer = isViewer;
window.applyRoleBasedVisibility = applyRoleBasedVisibility;
window.updateDashboardForRole = updateDashboardForRole;

/**
 * Redirect user based on their role
 * Used in dashboard pages to ensure users only access their designated dashboard
 */
function redirectBasedOnRole() {
    const user = getCurrentUser();
    const userRole = user?.role || 'user';

    // Get current page
    const currentPage = window.location.pathname.split('/').pop();

    // Define allowed pages per role
    // Creators now share dashboard.html with regular users
    // Admins use admin.html
    const allowedPages = {
        'admin': ['admin.html', 'index.html', 'video.html', 'dashboard.html'],
        'creator': ['dashboard.html', 'index.html', 'favorites.html', 'history.html', 'profile.html', 'video.html'],
        'user': ['dashboard.html', 'index.html', 'favorites.html', 'history.html', 'profile.html', 'video.html']
    };

    // Check if current page is allowed for this role
    const currentAllowed = allowedPages[userRole] || allowedPages['user'];

    if (!currentAllowed.includes(currentPage)) {
        // Redirect to appropriate dashboard
        const redirectUrl = userRole === 'admin' ? 'admin.html' : 'dashboard.html';
        window.location.href = redirectUrl;
    }
}

/**
 * Check if user has permission for specific action
 * @param {string} action - The action to check
 * @returns {boolean}
 */
function hasPermission(action) {
    const user = getCurrentUser();
    if (!user) return false;

    const permissions = {
        'upload': ['creator', 'user'],
        'approve_video': ['admin'],
        'manage_users': ['admin'],
        'delete_video': ['admin'],
        'moderate_comments': ['admin']
    };

    const allowedRoles = permissions[action] || [];
    return allowedRoles.includes(user.role);
}

// Make redirectBasedOnRole available globally
window.redirectBasedOnRole = redirectBasedOnRole;
window.hasPermission = hasPermission;


