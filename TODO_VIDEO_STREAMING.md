# Video Streaming Refinement - Implementation Plan

## Goal
Improve video streaming functionality to enable posting videos from frontend, storing in database, and fetching them properly for end users.

## Tasks

### Phase 1: Backend Improvements ✅ COMPLETED
- [x] 1.1 Add `uploadFromServer` method to VideoController for uploading existing video files
- [x] 1.2 Add `getStreamInfo` method for streaming metadata
- [x] 1.3 Add `listServerVideos` method to scan storage directory
- [x] 1.4 Add `importFromServer` method for quick imports
- [x] 1.5 Add `extractVideoDuration` helper method with FFprobe/getid3 support
- [x] 1.6 Add `formatBytes` helper method

### Phase 2: API Routes ✅ COMPLETED
- [x] 2.1 Add route for server-side upload: `POST /api/videos/upload-from-server`
- [x] 2.2 Add route for video stream info: `GET /api/videos/{video}/stream-info`
- [x] 2.3 Add route for listing server videos: `GET /api/videos/server/list`
- [x] 2.4 Add route for importing from server: `POST /api/videos/import-from-server`

### Phase 3: Frontend Dashboard Improvements ✅ COMPLETED
- [x] 3.1 Add "Upload from Server" tab in dashboard.html
- [x] 3.2 Add file path input for server files
- [x] 3.3 Add "Import from Server Storage" tab
- [x] 3.4 Add server video scanning functionality
- [x] 3.5 Add import form with category selection

### Phase 4: Frontend JavaScript Enhancements ✅ COMPLETED
- [x] 4.1 Add `uploadFromServer()` function in app.js
- [x] 4.2 Add `listServerVideos()` function to scan storage directory
- [x] 4.3 Add `importFromServer()` function for quick imports
- [x] 4.4 Add `getVideoStreamInfo()` function for streaming metadata
- [x] 4.5 Add `loadVideoDetails()` enhanced function for better playback

### Phase 5: Testing & Verification
- [ ] 5.1 Test video upload from frontend
- [ ] 5.2 Test video playback from frontend
- [ ] 5.3 Test video listing and fetching
- [ ] 5.4 Verify streaming works with HTTP Range requests

## Implementation Summary

### Backend Changes (VideoController.php)
- Added `uploadFromServer()` - Copy video from filesystem to storage and create DB record
- Added `getStreamInfo()` - Return streaming metadata (file size, mime type, duration, stream URL)
- Added `listServerVideos()` - Scan storage/app/public/videos directory
- Added `importFromServer()` - Quick import for existing storage files
- Added `extractVideoDuration()` - Extract duration using FFprobe or getid3
- Added `formatBytes()` - Format file size to human readable

### API Routes (api.php)
```php
POST /api/videos/upload-from-server    // Upload existing server file
GET  /api/videos/{video}/stream-info   // Get streaming metadata
GET  /api/videos/server/list           // List available server videos
POST /api/videos/import-from-server    // Import from storage
```

### Frontend Changes (dashboard.html)
- Added tabbed upload interface:
  - Tab 1: Upload from Computer (standard browser upload)
  - Tab 2: Upload from Server (specify file path on server)
  - Tab 3: Import from Server Storage (scan and select from storage)
- Added server video scanning and display
- Added import form with file info and category selection

### Frontend JS Changes (app.js)
- Added `listServerVideos()` - Call API to list storage videos
- Added `importFromServer()` - Import selected video
- Added `uploadFromServer()` - Upload via file path
- Added `getVideoStreamInfo()` - Get streaming metadata
- Added `loadVideoDetails()` - Enhanced video loading with stream URL

## How It Works

### 1. Upload from Computer (Standard)
- User selects video file from their computer
- File uploaded via chunked upload (for large files)
- Video metadata saved to database
- Video stored in `storage/app/public/videos/`

### 2. Upload from Server
- User provides full path to video file on server
- System copies file to storage directory
- Creates database record with metadata
- Useful for files already on server

### 3. Import from Server Storage
- System scans `storage/app/public/videos/` directory
- Displays available files not yet in database
- User selects file and provides metadata
- Quick import creates database entry
- No file copying needed (already in place)

### 4. Video Playback
- Frontend calls `loadVideoDetails(id)` to get video info
- API returns `stream_url` for video player
- Video player uses streaming endpoint with HTTP Range support
- Progressive download for large files

## Testing Commands

```bash
# Test server is running
curl http://localhost/api/videos

# Test streaming endpoint
curl -I http://localhost/api/videos/1/stream

# List server videos (requires auth)
curl -H "Authorization: Bearer {token}" http://localhost/api/videos/server/list
```

## Notes
- Videos stored in `storage/app/public/videos/`
- Thumbnails stored in `storage/app/public/thumbnails/`
- Streaming endpoint: `/api/videos/{id}/stream`
- Use HTTP Range header for progressive download
- FFprobe/getid3 required for duration extraction (optional fallback available)

