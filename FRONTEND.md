# iTechTube Frontend Implementation

## Overview
Complete frontend for the StreamFlix video streaming platform built with HTML, CSS (Bootstrap 5), and vanilla JavaScript.

## Pages Created

### 1. index.html - Home/Browse Page
- Hero section with call-to-action
- Category grid for browsing
- Video grid with pagination
- Search functionality
- Sort by latest/popular
- Responsive design

### 2. login.html - Login Page
- Email/password form
- Form validation
- Error handling
- Redirect to dashboard on success

### 3. register.html - Registration Page
- Name/email/password form
- Password confirmation
- Form validation
- Auto-login on registration

### 4. dashboard.html - User Dashboard
- Video upload with progress bar
- My Videos management (view/edit/delete)
- Playlist CRUD operations
- Continue Watching section
- Category dropdown for uploads

### 5. video.html - Video Player Page
- Video streaming with progressive download
- Watch progress tracking
- Continue Watching resume button
- Related videos sidebar
- Add to playlist dropdown
- Social sharing (Facebook, Twitter, LinkedIn)
- Keyboard shortcuts:
  - Space/K: Play/Pause
  - Left/Right arrows: Seek ±10s
  - Up/Down arrows: Volume ±10%
  - M: Mute toggle
  - F: Fullscreen

### 6. playlist.html - Playlist View Page
- Playlist details header
- Video list with ordering
- Shuffle playlist
- Remove videos (for owner)
- Share playlist

## Features Implemented

### Authentication
- JWT-like token storage in localStorage
- Login/Register/Logout flow
- Protected routes (dashboard, upload)
- Auto-redirect for unauthenticated users

### Video Management
- Upload with progress tracking
- Video streaming endpoint integration
- Thumbnail support
- Category assignment
- Delete functionality

### Watch History
- Automatic progress tracking
- "Continue Watching" feature
- History persistence

### Playlists
- Create/Edit/Delete playlists
- Public/Private visibility
- Add/Remove videos
- Shuffle playback

### Search & Discovery
- Video search by title/description
- Category filtering
- Pagination
- Sort options

## File Structure

```
public/frontend/
├── index.html          # Home/browse page
├── login.html          # Login page
├── register.html       # Registration page
├── dashboard.html      # User dashboard
├── video.html          # Video player page
├── playlist.html       # Playlist viewing page
├── css/
│   └── style.css       # Custom styles (~500 lines)
└── js/
    └── app.js          # Main JavaScript (~700 lines)
```

## API Integration

### Authentication Endpoints
- `POST /api/register` - Register new user
- `POST /api/login` - Login and get token
- `POST /api/logout` - Revoke token

### Category Endpoints
- `GET /api/categories` - List all categories

### Video Endpoints
- `GET /api/videos` - List videos (paginated)
- `GET /api/videos/{id}` - Get video details
- `GET /api/videos/{id}/stream` - Stream video content
- `GET /api/videos/search` - Search videos
- `POST /api/videos` - Upload video (multipart)
- `DELETE /api/videos/{id}` - Delete video
- `GET /api/my-videos` - Get user's videos
- `POST /api/videos/{id}/watch` - Record watch progress

### Playlist Endpoints
- `GET /api/playlists` - List user playlists
- `POST /api/playlists` - Create playlist
- `GET /api/playlists/{id}` - Get playlist details
- `PUT /api/playlists/{id}` - Update playlist
- `DELETE /api/playlists/{id}` - Delete playlist
- `POST /api/playlists/{id}/videos` - Add video
- `DELETE /api/playlists/{id}/videos/{vid}` - Remove video

### History Endpoints
- `GET /api/history` - Get watch history
- `GET /api/history/continue-watching` - Get continue watching

## Setup Instructions

### 1. Start Backend
```bash
cd /home/prosper/itechtube
php artisan serve
```

### 2. Access Frontend
- Option 1: Open `/home/prosper/itechtube/public/frontend/index.html` directly in browser
- Option 2: Access via `http://127.0.0.1:8000/frontend/index.html`

### 3. Test Flow
1. Register a new account at `/frontend/register.html`
2. Login at `/frontend/login.html`
3. Go to Dashboard to upload a video
4. Browse videos on home page
5. Watch videos and track progress
6. Create playlists and add videos

## Technologies Used

- **Bootstrap 5.3** - CSS framework
- **Font Awesome 6.4** - Icons
- **Vanilla JavaScript** - No frameworks
- **localStorage** - Token/user storage
- **Fetch API** - HTTP requests

## CSS Customization

Custom CSS variables in `style.css`:
```css
:root {
    --primary-yellow: #FFD700;
    --primary-red: #DC3545;
    --primary-black: #000000;
    --dark-gray: #212529;
    --light-gray: #6c757d;
}
```

## Browser Support

- Chrome/Edge (recommended)
- Firefox
- Safari
- Mobile browsers (responsive)

## Keyboard Shortcuts (Video Player)

| Key | Action |
|-----|--------|
| Space/K | Play/Pause |
| ← | Seek -10s |
| → | Seek +10s |
| ↑ | Volume +10% |
| ↓ | Volume -10% |
| M | Mute/Unmute |
| F | Toggle Fullscreen |

