# Thunder Client API Testing Guide

This guide provides step-by-step instructions on how to test the Streaming Platform API endpoints using Thunder Client in VS Code.

## Table of Contents
- [Prerequisites](#prerequisites)
- [Setting Up Thunder Client](#setting-up-thunder-client)
- [Authentication Endpoints](#authentication-endpoints)
- [Category Endpoints](#category-endpoints)
- [Video Endpoints](#video-endpoints)
- [Playlist Endpoints](#playlist-endpoints)
- [Watch History Endpoints](#watch-history-endpoints)
- [Video Streaming](#video-streaming)
- [Common Testing Scenarios](#common-testing-scenarios)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

### 1. Start the Laravel Development Server
```bash
cd /home/prosper/itechtube
php artisan serve
```
The API will be available at: `http://127.0.0.1:8000`

### 2. Ensure MySQL/XAMPP is Running
- Start XAMPP MySQL service
- Ensure database `streamflix` exists
- Run migrations if needed:
```bash
php artisan migrate
```

### 3. Install Thunder Client Extension
1. Open VS Code
2. Go to Extensions (Ctrl+Shift+X)
3. Search for "Thunder Client"
4. Install the extension by "Ranga Vadhineni"

---

## Setting Up Thunder Client

### 1. Create a New Collection
1. Click on Thunder Client icon in VS Code sidebar
2. Click "New Collection"
3. Name it: `Streamflix API`

### 2. Set Environment Variables
1. Click the "Env" button (gear icon)
2. Add the following variables:
```json
{
  "baseUrl": "http://127.0.0.1:8000/api",
  "token": "",
  "userId": ""
}
```

### 3. Authentication Setup
All authenticated requests require a Bearer token in the header:
- **Header**: `Authorization: Bearer {{token}}`

---

## Authentication Endpoints

### 1. Register New User

**Request:**
```
POST {{baseUrl}}/register
Content-Type: application/json
```

**Body:**
```json
{
  "name": "Test User",
  "email": "testuser@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Expected Response (201 Created):**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "Test User",
    "email": "testuser@example.com",
    "created_at": "...",
    "updated_at": "..."
  },
  "token": "1|abcdef123456..."
}
```

**Testing Steps:**
1. Click Send
2. Copy the `token` value
3. Update `{{token}}` in Environment Variables
4. Save the response user `id` to `{{userId}}`

---

### 2. Login User

**Request:**
```
POST {{baseUrl}}/login
Content-Type: application/json
```

**Body:**
```json
{
  "email": "testuser@example.com",
  "password": "password123"
}
```

**Expected Response (200 OK):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Test User",
    "email": "testuser@example.com"
  },
  "token": "2|abcdef123456..."
}
```

---

### 3. Get User Profile

**Request:**
```
GET {{baseUrl}}/user
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "id": 1,
  "name": "Test User",
  "email": "testuser@example.com",
  "avatar": null,
  "role": "user",
  "is_active": true,
  "created_at": "...",
  "updated_at": "..."
}
```

---

### 4. Logout User

**Request:**
```
POST {{baseUrl}}/logout
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "Logged out successfully"
}
```

**Note:** The token is now invalidated and cannot be used again.

---

## Category Endpoints

### 1. List All Categories (Public)

**Request:**
```
GET {{baseUrl}}/categories
```

**Expected Response (200 OK):**
```json
{
  "message": "Categories retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Technology",
      "slug": "technology",
      "description": "Tech tutorials and reviews",
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

---

### 2. Get Single Category (Public)

**Request:**
```
GET {{baseUrl}}/categories/1
```

**Expected Response (200 OK):**
```json
{
  "message": "Category retrieved successfully",
  "data": {
    "id": 1,
    "name": "Technology",
    "slug": "technology",
    "description": "Tech tutorials and reviews"
  }
}
```

---

### 3. Create Category (Authenticated)

**Request:**
```
POST {{baseUrl}}/categories
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
  "name": "Music",
  "description": "Music videos and tutorials"
}
```

**Expected Response (201 Created):**
```json
{
  "message": "Category created successfully",
  "data": {
    "id": 2,
    "name": "Music",
    "slug": "music",
    "description": "Music videos and tutorials",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

---

### 4. Update Category (Authenticated)

**Request:**
```
PUT {{baseUrl}}/categories/2
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
  "name": "Music & Entertainment",
  "description": "Updated description"
}
```

**Expected Response (200 OK):**
```json
{
  "message": "Category updated successfully",
  "data": {
    "id": 2,
    "name": "Music & Entertainment",
    "slug": "music-entertainment",
    "description": "Updated description"
  }
}
```

---

### 5. Delete Category (Authenticated)

**Request:**
```
DELETE {{baseUrl}}/categories/2
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "Category deleted successfully"
}
```

---

## Video Endpoints

### 1. List All Videos (Public)

**Request:**
```
GET {{baseUrl}}/videos
```

**Query Parameters:**
- `category_id` (optional): Filter by category
- `sort_by` (optional): `created_at`, `views_count`, `title`
- `sort_order` (optional): `asc`, `desc`
- `per_page` (optional): Number of results per page (default: 10)

**Example with filters:**
```
GET {{baseUrl}}/videos?category_id=1&sort_by=views_count&sort_order=desc&per_page=5
```

**Expected Response (200 OK):**
```json
{
  "message": "Videos retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "title": "Laravel Tutorial",
        "description": "Learn Laravel from scratch",
        "file_size": 104857600,
        "duration": 1200,
        "views_count": 1500,
        "status": "active",
        "created_at": "...",
        "user": {
          "id": 1,
          "name": "John Doe",
          "avatar": null
        },
        "category": {
          "id": 1,
          "name": "Technology",
          "slug": "technology"
        }
      }
    ],
    "links": {...},
    "meta": {...}
  }
}
```

---

### 2. Get Single Video (Public)

**Request:**
```
GET {{baseUrl}}/videos/1
```

**Note:** This increments the view count.

**Expected Response (200 OK):**
```json
{
  "message": "Video retrieved successfully",
  "data": {
    "id": 1,
    "title": "Laravel Tutorial",
    "description": "Learn Laravel from scratch",
    "file_path": "videos/abc123.mp4",
    "thumbnail_path": "thumbnails/abc123.jpg",
    "file_size": 104857600,
    "duration": 1200,
    "views_count": 1501,
    "status": "active",
    "user": {...},
    "category": {...}
  }
}
```

---

### 3. Search Videos (Public)

**Request:**
```
GET {{baseUrl}}/videos/search?q=tutorial
```

**Query Parameters:**
- `q` (required): Search term (min 2 characters)
- `category_id` (optional): Filter by category
- `sort_by` (optional): `created_at`, `views_count`, `title`
- `sort_order` (optional): `asc`, `desc`
- `per_page` (optional): Number of results per page

**Example:**
```
GET {{baseUrl}}/videos/search?q=laravel&category_id=1&sort_by=views_count
```

**Expected Response (200 OK):**
```json
{
  "message": "Search results retrieved successfully",
  "data": [...],
  "search_term": "laravel"
}
```

---

### 4. Upload Video (Authenticated)

**Request:**
```
POST {{baseUrl}}/videos
Authorization: Bearer {{token}}
Content-Type: multipart/form-data
```

**Body (form-data):**
| Key | Type | Value |
|-----|------|-------|
| title | text | My New Video |
| description | text | Video description |
| category_id | text | 1 |
| video | file | [Select MP4 file] |
| thumbnail | file | [Select Image file] |

**Validation Rules:**
- `title`: Required, max 255 characters
- `description`: Optional
- `category_id`: Optional, must exist in categories table
- `video`: Required, file (mp4, mov, avi, mkv, webm), max 500MB
- `thumbnail`: Optional, image (jpg, jpeg, png, webp), max 5MB

**Expected Response (201 Created):**
```json
{
  "message": "Video uploaded successfully",
  "data": {
    "id": 1,
    "title": "My New Video",
    "description": "Video description",
    "file_path": "videos/uuid.mp4",
    "thumbnail_path": "thumbnails/uuid.jpg",
    "file_size": 52428800,
    "duration": 0,
    "views_count": 0,
    "status": "active",
    "user": {...},
    "category": {...}
  }
}
```

---

### 5. Update Video (Authenticated)

**Request:**
```
PUT {{baseUrl}}/videos/1
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
  "title": "Updated Video Title",
  "description": "Updated description",
  "category_id": 2,
  "status": "active"
}
```

**Expected Response (200 OK):**
```json
{
  "message": "Video updated successfully",
  "data": {
    "id": 1,
    "title": "Updated Video Title",
    ...
  }
}
```

**Note:** You can only update your own videos.

---

### 6. Delete Video (Authenticated)

**Request:**
```
DELETE {{baseUrl}}/videos/1
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "Video deleted successfully"
}
```

**Note:** This also removes the video and thumbnail files from storage.

---

### 7. Get My Videos (Authenticated)

**Request:**
```
GET {{baseUrl}}/my-videos
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "User videos retrieved successfully",
  "data": [...]
}
```

---

### 8. Record Watch Progress (Authenticated)

**Request:**
```
POST {{baseUrl}}/videos/1/watch
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
  "progress": 120,
  "completed": false
}
```

**Expected Response (201 Created):**
```json
{
  "message": "Watch progress recorded successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "video_id": 1,
    "progress": 120,
    "completed": false,
    "watched_at": "..."
  }
}
```

---

## Playlist Endpoints

### 1. List My Playlists (Authenticated)

**Request:**
```
GET {{baseUrl}}/playlists
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "Playlists retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "My Favorites",
        "description": "My favorite videos",
        "is_public": true,
        "videos_count": 5,
        "created_at": "...",
        "updated_at": "..."
      }
    ],
    "links": {...},
    "meta": {...}
  }
}
```

---

### 2. Create Playlist (Authenticated)

**Request:**
```
POST {{baseUrl}}/playlists
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
  "name": "Watch Later",
  "description": "Videos to watch later",
  "is_public": false
}
```

**Expected Response (201 Created):**
```json
{
  "message": "Playlist created successfully",
  "data": {
    "id": 2,
    "name": "Watch Later",
    "description": "Videos to watch later",
    "is_public": false,
    "user": {...},
    "videos": []
  }
}
```

---

### 3. Get Playlist (Authenticated)

**Request:**
```
GET {{baseUrl}}/playlists/1
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "Playlist retrieved successfully",
  "data": {
    "id": 1,
    "name": "My Favorites",
    "is_public": true,
    "user": {...},
    "videos": [
      {
        "id": 1,
        "title": "Video Title",
        "thumbnail_path": "...",
        "duration": 1200,
        "pivot": {
          "playlist_id": 1,
          "video_id": 1,
          "position": 0
        }
      }
    ]
  }
}
```

**Note:** You can view your private playlists and public playlists of others.

---

### 4. Update Playlist (Authenticated)

**Request:**
```
PUT {{baseUrl}}/playlists/1
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
  "name": "Updated Playlist Name",
  "description": "Updated description",
  "is_public": true
}
```

**Expected Response (200 OK):**
```json
{
  "message": "Playlist updated successfully",
  "data": {...}
}
```

---

### 5. Delete Playlist (Authenticated)

**Request:**
```
DELETE {{baseUrl}}/playlists/1
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "Playlist deleted successfully"
}
```

---

### 6. Add Video to Playlist (Authenticated)

**Request:**
```
POST {{baseUrl}}/playlists/1/videos
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
  "video_id": 5
}
```

**Expected Response (201 Created):**
```json
{
  "message": "Video added to playlist successfully",
  "data": {
    "id": 1,
    "name": "My Favorites",
    "videos": [
      {
        "id": 5,
        "title": "Another Video",
        "pivot": {
          "playlist_id": 1,
          "video_id": 5,
          "position": 0
        }
      }
    ]
  }
}
```

**Error Response (Video already in playlist - 422):**
```json
{
  "message": "Video already exists in this playlist"
}
```

---

### 7. Remove Video from Playlist (Authenticated)

**Request:**
```
DELETE {{baseUrl}}/playlists/1/videos/5
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "Video removed from playlist successfully",
  "data": {...}
}
```

---

### 8. Reorder Videos in Playlist (Authenticated)

**Request:**
```
PUT {{baseUrl}}/playlists/1/reorder
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
  "video_ids": [5, 3, 1, 2, 4]
}
```

**Expected Response (200 OK):**
```json
{
  "message": "Videos reordered successfully",
  "data": {...}
}
```

**Note:** The video IDs array determines the new order (position 0, 1, 2, etc.)

---

## Watch History Endpoints

### 1. Get Watch History (Authenticated)

**Request:**
```
GET {{baseUrl}}/history
Authorization: Bearer {{token}}
```

**Query Parameters:**
- `incomplete` (optional): `true` to show only incomplete videos
- `completed` (optional): `true` to show only completed videos

**Example:**
```
GET {{baseUrl}}/history?incomplete=true
```

**Expected Response (200 OK):**
```json
{
  "message": "Watch history retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "video_id": 5,
        "progress": 120,
        "completed": false,
        "watched_at": "...",
        "video": {
          "id": 5,
          "title": "Video Title",
          "thumbnail_path": "...",
          "duration": 600,
          ...
        }
      }
    ],
    "links": {...},
    "meta": {...}
  }
}
```

---

### 2. Record Watch Progress (Authenticated)

**Request:**
```
POST {{baseUrl}}/history
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
  "video_id": 5,
  "progress": 180,
  "completed": false
}
```

**Expected Response (201 Created):**
```json
{
  "message": "Watch progress recorded successfully",
  "data": {
    "id": 2,
    "user_id": 1,
    "video_id": 5,
    "progress": 180,
    "completed": false,
    "watched_at": "..."
  }
}
```

**Note:** If an entry already exists, it will be updated.

---

### 3. Get Watch Progress for Specific Video

**Request:**
```
GET {{baseUrl}}/history/video/5
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "Watch history retrieved successfully",
  "data": {
    "id": 2,
    "user_id": 1,
    "video_id": 5,
    "progress": 180,
    "completed": false,
    "watched_at": "..."
  }
}
```

**If no history exists:**
```json
{
  "message": "No watch history found for this video",
  "data": null
}
```

---

### 4. Update Watch Progress

**Request:**
```
PUT {{baseUrl}}/history/video/5
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
  "progress": 300,
  "completed": true
}
```

**Expected Response (200 OK):**
```json
{
  "message": "Watch progress updated successfully",
  "data": {...}
}
```

---

### 5. Delete Watch History Entry

**Request:**
```
DELETE {{baseUrl}}/history/2
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "Watch history entry deleted successfully"
}
```

---

### 6. Clear All Watch History

**Request:**
```
DELETE {{baseUrl}}/history
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "All watch history cleared successfully"
}
```

---

### 7. Get Continue Watching List

**Request:**
```
GET {{baseUrl}}/history/continue-watching
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "message": "Continue watching list retrieved successfully",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "video_id": 5,
      "progress": 120,
      "completed": false,
      "video": {...}
    }
  ]
}
```

**Note:** Returns incomplete videos sorted by most recent watch activity.

---

## Video Streaming

### Stream Video

**Request:**
```
GET {{baseUrl}}/videos/1/stream
Range: bytes=0-1048575
```

**Headers:**
- `Range`: Optional. Use for partial content streaming.
  - Example: `bytes=0-1048575` (first 1MB)

**Expected Response (206 Partial Content):**
- Status: `206 Partial Content`
- Headers:
  - `Content-Type: video/mp4`
  - `Content-Length: 1048576`
  - `Content-Range: bytes 0-1048575/52428800`
  - `Accept-Ranges: bytes`

**Without Range header:**
- Returns full video file
- Status: `200 OK`

**Note:** Use a video player or browser to test streaming. The view count is incremented on each stream request.

---

## Common Testing Scenarios

### Scenario 1: Complete User Workflow

1. **Register User**
   ```
   POST {{baseUrl}}/register
   ```

2. **Login**
   ```
   POST {{baseUrl}}/login
   ```
   → Save token to `{{token}}`

3. **Create Categories**
   ```
   POST {{baseUrl}}/categories (3 times)
   ```

4. **Upload Video**
   ```
   POST {{baseUrl}}/videos (upload video file)
   ```
   → Save video ID

5. **Create Playlist**
   ```
   POST {{baseUrl}}/playlists
   ```

6. **Add Video to Playlist**
   ```
   POST {{baseUrl}}/playlists/1/videos
   {"video_id": 1}
   ```

7. **Record Watch Progress**
   ```
   POST {{baseUrl}}/videos/1/watch
   {"progress": 60, "completed": false}
   ```

8. **Check Watch History**
   ```
   GET {{baseUrl}}/history
   ```

9. **Get Continue Watching**
   ```
   GET {{baseUrl}}/history/continue-watching
   ```

10. **Logout**
    ```
    POST {{baseUrl}}/logout
    ```

---

### Scenario 2: Video Owner Workflow

1. **Login as video owner**
2. **Update your video**
   ```
   PUT {{baseUrl}}/videos/1
   {"title": "Updated Title"}
   ```
3. **Get your videos**
   ```
   GET {{baseUrl}}/my-videos
   ```
4. **Delete your video**
   ```
   DELETE {{baseUrl}}/videos/1
   ```

---

### Scenario 3: Public Video Access

1. **List public videos**
   ```
   GET {{baseUrl}}/videos
   ```
2. **Search videos**
   ```
   GET {{baseUrl}}/videos/search?q=tutorial
   ```
3. **Get video details** (view count increments)
   ```
   GET {{baseUrl}}/videos/1
   ```
4. **Stream video** (view count increments)
   ```
   GET {{baseUrl}}/videos/1/stream
   ```

---

### Scenario 4: Authorization Testing

1. **Try to access protected endpoint without token**
   ```
   GET {{baseUrl}}/videos
   ```
   → Should work (public endpoint)

   ```
   GET {{baseUrl}}/playlists
   ```
   → Should return `401 Unauthorized`

2. **Try to access another user's private playlist**
   ```
   GET {{baseUrl}}/playlists/999
   ```
   → Should return `403 Forbidden`

3. **Try to update another user's video**
   ```
   PUT {{baseUrl}}/videos/999
   ```
   → Should return `403 Forbidden`

---

## Troubleshooting

### Error: "Unauthenticated"

**Cause:** Missing or invalid Bearer token.

**Solution:**
1. Ensure you're logged in
2. Check token format: `Authorization: Bearer {{token}}`
3. Token may have been revoked - login again

---

### Error: "Validation failed"

**Cause:** Invalid or missing input data.

**Solution:**
1. Check response for specific validation errors
2. Ensure required fields are present
3. Verify data types and formats

**Example Response:**
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

---

### Error: "Unauthorized to view this playlist"

**Cause:** Trying to access another user's private playlist.

**Solution:**
1. Ensure you own the playlist, or
2. The playlist must be public (`is_public: true`)

---

### Error: "Unauthorized. You can only update your own videos."

**Cause:** Trying to modify another user's video.

**Solution:**
1. Only modify videos you uploaded
2. Check `user_id` in the video response

---

### Error: "Video already exists in this playlist"

**Cause:** The video is already added to the playlist.

**Solution:**
1. Remove the video first, then add again if needed
2. Or use a different video

---

### Error: "Video file not found" (404)

**Cause:** Video file doesn't exist on disk.

**Solution:**
1. Check if file exists in `storage/app/public/videos/`
2. Verify `file_path` in database matches actual file location

---

### Error: CORS Issues

**Cause:** Browser blocking cross-origin requests.

**Solution:**
1. Check `config/cors.php` configuration
2. Ensure your origin is in `allowed_origins`

---

### Error: Database Connection Failed

**Cause:** MySQL not running or wrong database configuration.

**Solution:**
1. Start XAMPP MySQL service
2. Check `.env` file for correct database credentials
3. Run migrations: `php artisan migrate`

---

## Rate Limiting

The API implements rate limiting. If you receive `429 Too Many Requests`:

- **Register**: 5 requests per minute
- **Login**: 5 requests per minute
- **Search**: 60 requests per minute
- **Upload**: 10 requests per minute
- **Streaming**: 60 requests per minute

Wait a few minutes before retrying.

---

## Success Response Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request succeeded |
| 201 | Created - Resource successfully created |
| 206 | Partial Content - Streaming video chunk |

## Client Error Response Codes

| Code | Meaning |
|------|---------|
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Missing/invalid token |
| 403 | Forbidden - Not authorized |
| 404 | Not Found - Resource doesn't exist |
| 422 | Unprocessable Entity - Validation failed |
| 429 | Too Many Requests - Rate limited |

## Server Error Response Codes

| Code | Meaning |
|------|---------|
| 500 | Internal Server Error |
| 503 | Service Unavailable |

---

## Tips for Testing

1. **Use Environment Variables**: Store base URL and tokens in Thunder Client environment
2. **Save Requests**: Save commonly used requests in your collection
3. **Use Collections**: Organize endpoints by resource type
4. **Test Both Success and Error Cases**: Don't just test happy paths
5. **Check Response Headers**: Especially for streaming endpoints
6. **Use Form-Data for File Uploads**: Set `Content-Type: multipart/form-data`

---

## Testing Checklist

- [ ] Register a new user
- [ ] Login and obtain token
- [ ] Get user profile
- [ ] Create categories
- [ ] List categories
- [ ] Upload a video
- [ ] List videos
- [ ] Search videos
- [ ] Get video details (verify view count increment)
- [ ] Stream video
- [ ] Create playlist
- [ ] Add video to playlist
- [ ] Remove video from playlist
- [ ] Reorder playlist videos
- [ ] Record watch progress
- [ ] Get watch history
- [ ] Get continue watching
- [ ] Clear watch history
- [ ] Update video
- [ ] Delete video
- [ ] Logout

---

This guide covers all API endpoints. Use it to test your implementation and verify all functionality works correctly.

