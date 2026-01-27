<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITechTube - Video Streaming Platform</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #0f0f0f;
            color: #f1f1f1;
            overflow-x: hidden;
        }

        /* Top Navigation */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background-color: #0f0f0f;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 16px;
            border-bottom: 1px solid #303030;
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff0000;
        }

        .search-container {
            flex: 0 1 640px;
            display: flex;
            margin: 0 40px;
        }

        .search-input {
            flex: 1;
            background: #121212;
            border: 1px solid #303030;
            padding: 0 16px;
            height: 40px;
            border-radius: 40px 0 0 40px;
            color: #f1f1f1;
            font-size: 16px;
            outline: none;
        }

        .search-btn {
            width: 64px;
            background: #222222;
            border: 1px solid #303030;
            border-left: none;
            border-radius: 0 40px 40px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff0000, #ff6b00);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
        }

        /* Main Layout */
        .container {
            display: flex;
            margin-top: 56px;
            min-height: calc(100vh - 56px);
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 240px;
            background-color: #0f0f0f;
            padding-top: 12px;
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            overflow-y: auto;
            border-right: 1px solid #303030;
            z-index: 999;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            cursor: pointer;
            transition: background-color 0.2s;
            gap: 24px;
        }

        .sidebar-item:hover {
            background-color: #272727;
        }

        .sidebar-item.active {
            background-color: #272727;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 240px;
            padding: 24px;
        }

        /* Video Player */
        .video-player-container {
            margin-bottom: 24px;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
        }

        .video-player {
            width: 100%;
            height: 540px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .video-player video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .video-info {
            padding: 16px 0;
            border-bottom: 1px solid #303030;
        }

        .video-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .video-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #aaa;
            font-size: 0.9rem;
        }

        .video-actions {
            display: flex;
            gap: 16px;
            margin-top: 16px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #272727;
            border: none;
            color: #f1f1f1;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        .action-btn:hover {
            background-color: #3d3d3d;
        }

        /* Video Grid */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .video-card {
            background: transparent;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .video-card:hover {
            transform: translateY(-4px);
        }

        .thumbnail {
            position: relative;
            width: 100%;
            height: 180px;
            background: #222;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .duration {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .video-details {
            display: flex;
            gap: 12px;
        }

        .channel-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00d9ff, #00ff88);
            flex-shrink: 0;
        }

        .video-meta h3 {
            font-size: 1rem;
            line-height: 1.4;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .video-meta p {
            color: #aaa;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* Categories */
        .categories {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .category-btn {
            background: #272727;
            color: #f1f1f1;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            white-space: nowrap;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .category-btn:hover {
            background-color: #3d3d3d;
        }

        .category-btn.active {
            background-color: #f1f1f1;
            color: #0f0f0f;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 1300px) {
            .sidebar {
                width: 72px;
            }

            .sidebar-item span {
                display: none;
            }

            .main-content {
                margin-left: 72px;
            }
        }

        @media (max-width: 768px) {
            .video-player {
                height: 300px;
            }

            .video-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }

            .search-container {
                display: none;
            }

            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        /* Player Controls */
        .player-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .video-player:hover .player-controls {
            opacity: 1;
        }

        .play-pause {
            background: rgba(0,0,0,0.7);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .progress-bar {
            flex: 1;
            height: 4px;
            background: rgba(255,255,255,0.3);
            margin: 0 20px;
            border-radius: 2px;
            position: relative;
            cursor: pointer;
        }

        .progress {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: #ff0000;
            border-radius: 2px;
            width: 30%;
        }

        .time-display {
            color: white;
            font-size: 0.9rem;
            min-width: 100px;
        }

        /* Loading Animation */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255,255,255,0.1);
            border-left-color: #ff0000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Top Navigation -->
    <header class="header">
        <div class="logo">
            <i class="fas fa-play-circle" style="color: #ff0000;"></i>
            <span>ITechTube</span>
        </div>

        <div class="search-container">
            <input type="text" class="search-input" placeholder="Search">
            <button class="search-btn">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <div class="user-actions">
            <i class="fas fa-video" style="font-size: 1.2rem; cursor: pointer;"></i>
            <i class="fas fa-bell" style="font-size: 1.2rem; cursor: pointer;"></i>
            <div class="user-avatar">JD</div>
        </div>
    </header>

    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-item active">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-fire"></i>
                <span>Trending</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-film"></i>
                <span>Subscriptions</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-folder"></i>
                <span>Library</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-history"></i>
                <span>History</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-clock"></i>
                <span>Watch Later</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-thumbs-up"></i>
                <span>Liked Videos</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-gamepad"></i>
                <span>Gaming</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-lightbulb"></i>
                <span>Learning</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-music"></i>
                <span>Music</span>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Video Player Section -->
            <div class="video-player-container">
                <div class="video-player">
                    <video id="mainVideo" controls poster="https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80">
                        <source src="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>

                    <!-- Custom Player Controls -->
                    <div class="player-controls">
                        <button class="play-pause" id="playPauseBtn">
                            <i class="fas fa-pause"></i>
                        </button>
                        <div class="progress-bar" id="progressBar">
                            <div class="progress" id="progress"></div>
                        </div>
                        <div class="time-display">
                            <span id="currentTime">0:00</span> / <span id="duration">10:00</span>
                        </div>
                    </div>
                </div>

                <div class="video-info">
                    <h1 class="video-title">Building a Modern Web Application with Laravel & Vue.js</h1>
                    <div class="video-stats">
                        <div>
                            <span>245,678 views</span> • <span>Streamed 2 days ago</span>
                        </div>
                        <div class="video-actions">
                            <button class="action-btn">
                                <i class="fas fa-thumbs-up"></i>
                                <span>12K</span>
                            </button>
                            <button class="action-btn">
                                <i class="fas fa-thumbs-down"></i>
                                <span>Dislike</span>
                            </button>
                            <button class="action-btn">
                                <i class="fas fa-share"></i>
                                <span>Share</span>
                            </button>
                            <button class="action-btn">
                                <i class="fas fa-save"></i>
                                <span>Save</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="categories">
                <button class="category-btn active">All</button>
                <button class="category-btn">Programming</button>
                <button class="category-btn">Web Development</button>
                <button class="category-btn">Machine Learning</button>
                <button class="category-btn">Mobile Apps</button>
                <button class="category-btn">Cloud Computing</button>
                <button class="category-btn">Cybersecurity</button>
                <button class="category-btn">Data Science</button>
            </div>

            <!-- Video Grid -->
            <div class="video-grid" id="videoGrid">
                <!-- Videos will be loaded here by JavaScript -->
            </div>

            <!-- Loading Spinner -->
            <div class="loading" id="loadingIndicator">
                <div class="spinner"></div>
            </div>
        </main>
    </div>

    <script>
        // Sample video data
        const videos = [
            {
                id: 1,
                title: "Introduction to React Hooks - Complete Guide",
                channel: "CodeWithAlex",
                views: "125K views",
                time: "2 weeks ago",
                duration: "24:18",
                thumbnail: "https://images.unsplash.com/photo-1633356122544-f134324a6cee?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80"
            },
            {
                id: 2,
                title: "Building REST APIs with Node.js and Express",
                channel: "BackendMaster",
                views: "89K views",
                time: "1 month ago",
                duration: "42:05",
                thumbnail: "https://images.unsplash.com/photo-1555066931-4365d14bab8c?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w-800&q=80"
            },
            {
                id: 3,
                title: "Learn Python in 1 Hour - Crash Course",
                channel: "PythonPro",
                views: "1.2M views",
                time: "6 months ago",
                duration: "58:32",
                thumbnail: "https://images.unsplash.com/photo-1526379879527-8559ecfcaec9?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80"
            },
            {
                id: 4,
                title: "Full Stack Development with Next.js 13",
                channel: "NextLevelDev",
                views: "56K views",
                time: "3 days ago",
                duration: "36:47",
                thumbnail: "https://images.unsplash.com/photo-1551650975-87deedd944c3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80"
            },
            {
                id: 5,
                title: "Docker & Kubernetes for Beginners",
                channel: "DevOpsGuru",
                views: "210K views",
                time: "1 week ago",
                duration: "51:12",
                thumbnail: "https://images.unsplash.com/photo-1627398242454-45a1465c2479?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80"
            },
            {
                id: 6,
                title: "Mastering TypeScript - Advanced Patterns",
                channel: "TypeScriptWizard",
                views: "78K views",
                time: "2 months ago",
                duration: "38:24",
                thumbnail: "https://images.unsplash.com/photo-1516116216624-53e697fedbea?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80"
            },
            {
                id: 7,
                title: "Building a Video Streaming Platform",
                channel: "ITechTube",
                views: "45K views",
                time: "5 days ago",
                duration: "29:55",
                thumbnail: "https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80"
            },
            {
                id: 8,
                title: "GraphQL vs REST - Which is Better?",
                channel: "APIDesign",
                views: "112K views",
                time: "3 weeks ago",
                duration: "33:41",
                thumbnail: "https://images.unsplash.com/photo-1457305237443-44c3d5a30b89?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80"
            }
        ];

        // DOM elements
        const videoGrid = document.getElementById('videoGrid');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const mainVideo = document.getElementById('mainVideo');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const progress = document.getElementById('progress');
        const progressBar = document.getElementById('progressBar');
        const currentTimeEl = document.getElementById('currentTime');
        const durationEl = document.getElementById('duration');
        const searchInput = document.querySelector('.search-input');
        const searchBtn = document.querySelector('.search-btn');

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Load videos
            loadVideos();

            // Setup video player controls
            setupVideoPlayer();

            // Setup search functionality
            setupSearch();

            // Setup sidebar navigation
            setupSidebar();

            // Setup category filters
            setupCategories();
        });

        // Load videos to the grid
        function loadVideos() {
            // Simulate loading delay
            setTimeout(() => {
                loadingIndicator.style.display = 'none';

                videos.forEach(video => {
                    const videoCard = document.createElement('div');
                    videoCard.className = 'video-card';
                    videoCard.innerHTML = `
                        <div class="thumbnail">
                            <img src="${video.thumbnail}" alt="${video.title}">
                            <div class="duration">${video.duration}</div>
                        </div>
                        <div class="video-details">
                            <div class="channel-avatar"></div>
                            <div class="video-meta">
                                <h3>${video.title}</h3>
                                <p>${video.channel}</p>
                                <p>${video.views} • ${video.time}</p>
                            </div>
                        </div>
                    `;

                    // Add click event to play video
                    videoCard.addEventListener('click', () => playVideo(video));

                    videoGrid.appendChild(videoCard);
                });
            }, 800);
        }

        // Setup video player controls
        function setupVideoPlayer() {
            // Play/Pause button
            playPauseBtn.addEventListener('click', () => {
                if (mainVideo.paused) {
                    mainVideo.play();
                    playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                } else {
                    mainVideo.pause();
                    playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                }
            });

            // Update play/pause button based on video state
            mainVideo.addEventListener('play', () => {
                playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
            });

            mainVideo.addEventListener('pause', () => {
                playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
            });

            // Update progress bar
            mainVideo.addEventListener('timeupdate', () => {
                const percent = (mainVideo.currentTime / mainVideo.duration) * 100;
                progress.style.width = `${percent}%`;

                // Update time display
                currentTimeEl.textContent = formatTime(mainVideo.currentTime);
                durationEl.textContent = formatTime(mainVideo.duration);
            });

            // Click on progress bar to seek
            progressBar.addEventListener('click', (e) => {
                const rect = progressBar.getBoundingClientRect();
                const pos = (e.clientX - rect.left) / rect.width;
                mainVideo.currentTime = pos * mainVideo.duration;
            });

            // Set initial duration display
            mainVideo.addEventListener('loadedmetadata', () => {
                durationEl.textContent = formatTime(mainVideo.duration);
            });
        }

        // Format time in MM:SS
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
        }

        // Play a selected video
        function playVideo(video) {
            // Update main video player with selected video
            const videoTitle = document.querySelector('.video-title');
            videoTitle.textContent = video.title;

            // Update video stats
            const videoStats = document.querySelector('.video-stats span');
            videoStats.innerHTML = `<span>${video.views}</span> • <span>${video.time}</span>`;

            // Scroll to top to show the video player
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Show a notification that we're playing the video
            showNotification(`Now playing: ${video.title}`);
        }

        // Setup search functionality
        function setupSearch() {
            searchBtn.addEventListener('click', performSearch);
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        }

        // Perform search
        function performSearch() {
            const query = searchInput.value.trim();
            if (query) {
                showNotification(`Searching for: "${query}"`);
                // In a real app, you would fetch search results from the API
            }
        }

        // Setup sidebar navigation
        function setupSidebar() {
            const sidebarItems = document.querySelectorAll('.sidebar-item');
            sidebarItems.forEach(item => {
                item.addEventListener('click', () => {
                    // Remove active class from all items
                    sidebarItems.forEach(i => i.classList.remove('active'));
                    // Add active class to clicked item
                    item.classList.add('active');

                    // Show notification
                    const itemName = item.querySelector('span').textContent;
                    showNotification(`Loading ${itemName}...`);
                });
            });
        }

        // Setup category filters
        function setupCategories() {
            const categoryBtns = document.querySelectorAll('.category-btn');
            categoryBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Remove active class from all buttons
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    btn.classList.add('active');

                    // Show notification
                    const category = btn.textContent;
                    showNotification(`Filtering by: ${category}`);
                });
            });
        }

        // Show notification
        function showNotification(message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                bottom: 24px;
                right: 24px;
                background: #ff0000;
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                z-index: 10000;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                animation: slideIn 0.3s ease-out;
            `;

            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);

            notification.textContent = message;
            document.body.appendChild(notification);

            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out forwards';

                // Add slideOut animation
                const slideOutStyle = document.createElement('style');
                slideOutStyle.textContent = `
                    @keyframes slideOut {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(100%); opacity: 0; }
                    }
                `;
                document.head.appendChild(slideOutStyle);

                // Remove element after animation
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>
