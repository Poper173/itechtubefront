home/prosper/itechtubefront/fix_all.sh</path>
<parameter name="content">#!/bin/bash
# Complete fix for video platform issues
# 1. Fix 403 permissions on thumbnails
# 2. Fix infinite recursion in loadCategories

echo "=== Fixing permissions ==="
chmod -R 755 ~/itechtube/storage/app/public
chmod -R 755 ~/itechtube/public/storage
ls -la ~/itechtube/public/storage

echo ""
echo "=== Fixing JavaScript infinite recursion ==="
cat > ~/itechtube/public/frontend/js/app.js << 'JSEOF'
/**
 * iTechTube - Video Streaming Platform Frontend
 * Fixed version with no infinite recursion
 */
const API_BASE_URL = 'http://127.0.0.1:8000/api';
const STORAGE_URL = 'http://127.0.0.1:8000/storage';

// Cache to prevent recursive calls
let categoriesLoading = false;
let categoriesCache = null;

function showAlert(message, type = 'info') {
    const div = document.createElement('div');
    div.className = 'alert alert-' + type;
    div.textContent = message;
    document.querySelector('.container')?.prepend(div);
    setTimeout(() => div.remove(), 5000);
}

function formatDuration(seconds) {
    if (!seconds) return '0:00';
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return m + ':' + s.toString().padStart(2, '0');
}

function formatNumber(num) {
    return num ? num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") : '0';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Auth functions
function getAuthToken() { return localStorage.getItem('auth_token'); }
function isAuthenticated() { return !!getAuthToken(); }
function getCurrentUser() { const u = localStorage.getItem('current_user'); return u ? JSON.parse(u) : null; }

function updateAuthUI() {
    const authLinks = document.getElementById('auth-links');
    const userMenu = document.getElementById('user-menu');
    if (isAuthenticated()) {
        if (authLinks) authLinks.classList.add('d-none');
        if (userMenu) userMenu.classList.remove('d-none');
    } else {
        if (authLinks) authLinks.classList.remove('d-none');
        if (userMenu) userMenu.classList.add('d-none');
    }
}

async function logout() {
    try { if (isAuthenticated()) await apiRequest('/logout', { method: 'POST' }); } catch(e) {}
    localStorage.removeItem('auth_token');
    localStorage.removeItem('current_user');
    updateAuthUI();
    window.location.href = 'index.html';
}

// API function
async function apiRequest(endpoint, options = {}) {
    const url = API_BASE_URL + endpoint;
    const token = getAuthToken();
    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    if (token) headers['Authorization'] = 'Bearer ' + token;
    const response = await fetch(url, { ...options, headers });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'API error');
    return data;
}

// FIXED: Categories with no recursion
async function loadCategories() {
    if (categoriesLoading) return categoriesCache || [];
    categoriesLoading = true;
    try {
        const response = await apiRequest('/categories');
        categoriesCache = response.data || response;
        return categoriesCache;
    } catch (error) {
        console.error('Error loading categories:', error);
        return [];
    } finally {
        categoriesLoading = false;
    }
}

function renderCategories(categories, containerId = 'categories-container') {
    const container = document.getElementById(containerId);
    if (!container) return;
    if (!categories || categories.length === 0) {
        container.innerHTML = '<div class="text-center text-muted">No categories</div>';
        return;
    }
    container.innerHTML = categories.map(c => `
        <div class="col-md-3 mb-3">
            <div class="card category-card h-100" data-category-id="${c.id}">
                <div class="card-body text-center">
                    <h5 class="card-title text-yellow">${escapeHtml(c.name)}</h5>
                    <p class="small text-muted">${escapeHtml(c.description || '')}</p>
                </div>
        </div>
    `).join('');

    container.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('click', () => {
            const id = card.dataset.categoryId;
            loadVideosByCategory(id);
            container.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
        });
    });
}

// Video functions
async function loadVideos(page = 1, filters = {}) {
    try {
        let endpoint = '/videos?page=' + page;
        if (filters.category_id) endpoint += '&category_id=' + filters.category_id;
        const response = await apiRequest(endpoint);
        return response.data;
    } catch (error) {
        console.error('Error loading videos:', error);
        return null;
    }
}

function loadVideosByCategory(categoryId, page = 1) {
    loadVideos(page, { category_id: categoryId }).then(data => {
        if (data) renderVideos(data.data || data, 'videos-container', true);
    });
}

function renderVideos(videos, containerId = 'videos-container', clearContainer = true) {
    const container = document.getElementById(containerId);
    if (!container) return;
    if (clearContainer) container.innerHTML = '';
    if (!videos || videos.length === 0) {
        container.innerHTML = '<div class="text-center text-muted">No videos found</div>';
        return;
    }
    container.innerHTML += videos.map(v => createVideoCard(v)).join('');
}

function createVideoCard(video) {
    const thumb = video.thumbnail_url || (video.thumbnail_path ? STORAGE_URL + '/' + video.thumbnail_path :
        'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect fill="%23333" width="320" height="180"/><text fill="%23666" x="50%" y="50%" text-anchor="middle">No Video</text></svg>');
    const link = 'video.html?id=' + video.id;
    return '<div class="col-md-4 video-card mb-3"><div class="card h-100"><a href="' + link + '"><img src="' + thumb + '" class="card-img-top"></a><div class="card-body"><h5 class="card-title"><a href="' + link + '" class="text-white text-decoration-none">' + escapeHtml(video.title) + '</a></h5><p class="small text-muted">' + escapeHtml(video.description || '').substring(0, 60) + '</p><small class="text-muted">' + formatNumber(video.views_count || 0) + ' views</small></div></div>';
}

function getVideoThumbnail(video) {
    if (video.thumbnail_url) return video.thumbnail_url;
    if (video.thumbnail_path) return STORAGE_URL + '/' + video.thumbnail_path;
    return 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect fill="%23333" width="320" height="180"/><text fill="%23666" x="50%" y="50%" text-anchor="middle">No Video</text></svg>';
}

async function loadVideoDetails(videoId) {
    try {
        const response = await apiRequest('/videos/' + videoId);
        const video = response.data;
        if (!video) return null;
        return { ...video, video_url: API_BASE_URL + '/videos/' + videoId + '/stream' };
    } catch (error) {
        console.error('Error loading video:', error);
        return null;
    }
}

function initializeVideoPlayer(videoId, videoUrl) {
    const video = document.getElementById('video-player');
    if (!video) return;
    video.src = videoUrl || API_BASE_URL + '/videos/' + videoId + '/stream';
}

// Initialize app
function initializeApp() {
    updateAuthUI();
    if (document.getElementById('categories-container')) {
        loadCategories().then(c => renderCategories(c));
    }
    if (document.getElementById('videos-container')) {
        loadVideos().then(data => { if (data) renderVideos(data.data || data, 'videos-container', true); });
    }
}

document.addEventListener('DOMContentLoaded', initializeApp);
window.showAlert = showAlert;
window.logout = logout;
window.isAuthenticated = isAuthenticated;
window.getCurrentUser = getCurrentUser;
window.formatDuration = formatDuration;
window.formatNumber = formatNumber;
window.escapeHtml = escapeHtml;
window.loadCategories = loadCategories;
window.loadVideos = loadVideos;
window.renderVideos = renderVideos;
window.createVideoCard = createVideoCard;
window.loadVideoDetails = loadVideoDetails;
window.getVideoThumbnail = getVideoThumbnail;
window.initializeVideoPlayer = initializeVideoPlayer;
JSEOF

echo ""
echo "=== All fixes applied! ==="
echo "Restart your server: cd ~/itechtube && php artisan serve"
