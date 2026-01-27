# Video Streaming Fix - 2MB Response Limit Resolution

## Problem Summary

The application was experiencing a **2MB response limit** preventing large video retrieval and streaming. This was caused by PHP using default system settings instead of custom configuration optimized for video streaming.

## Root Cause

- No custom `php.ini` file existed in the project
- PHP was using system defaults:
  - `upload_max_filesize` = 2M (default)
  - `post_max_size` = 8M (default)
  - `max_execution_time` = 30s (default)
  - `memory_limit` = 128M (default)

## Solution Applied

Created a custom `php.ini` file with optimized settings for video streaming:

| Setting | Previous Value | New Value | Purpose |
|---------|---------------|-----------|---------|
| `upload_max_filesize` | 2M | **500M** | Large video uploads |
| `post_max_size` | 8M | **600M** | Handle POST data |
| `max_execution_time` | 30s | **300s** | Video processing time |
| `max_input_time` | 60s | **300s** | Input timeout |
| `memory_limit` | 128M | **512M** | Video processing memory |
| `output_buffering` | 4096 | **0** | Clean streaming |
| `max_file_uploads` | 20 | **50** | Multiple uploads |
| `session.gc_maxlifetime` | 1440s | **86400s** | Chunked upload sessions |
| `zlib.output_compression` | On | **Off** | Disable compression for streaming |

## Files Created/Modified

### Created: `php.ini`
Custom PHP configuration optimized for video streaming.

## How to Use

### 1. Start the Server

Use the provided startup script that automatically uses the custom php.ini:

```bash
# Option 1: Using the startup script
./start-server.sh

# Option 2: Manual command
php -c php.ini artisan serve --host=127.0.0.1 --port=8000
```

### 2. Verify Configuration

The startup script will display the configuration:

```
==========================================
  ItechTube Server Startup
==========================================

✓ Custom php.ini found
  - upload_max_filesize: 500M
  - post_max_size: 600M
  - max_execution_time: 300
```

### 3. Verify via PHP Info

Create a temporary route or check via CLI:

```bash
# Check current PHP settings
php -i | grep -E "(upload_max_filesize|post_max_size|max_execution_time|memory_limit)"
```

Expected output:
```
upload_max_filesize => 500M
post_max_size => 600M
max_execution_time => 300
memory_limit => 512M
```

## Testing Video Streaming

### Test 1: Direct Video File Upload

```bash
# Upload a video file (example using curl)
curl -X POST http://127.0.0.1:8000/api/videos \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "title=Test Video" \
  -F "video=@/path/to/large-video.mp4"
```

### Test 2: Chunked Upload (Recommended for Large Files)

```bash
# Step 1: Initialize chunked upload
curl -X POST http://127.0.0.1:8000/api/videos/upload/init \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "file_name": "large-video.mp4",
    "file_size": 524288000,
    "mime_type": "video/mp4",
    "chunk_size": 10485760,
    "total_chunks": 50,
    "title": "My Large Video"
  }'

# Step 2: Upload chunks
# Use the session_id returned from step 1
# Repeat for each chunk:
curl -X POST http://127.0.0.1:8000/api/videos/upload/chunk \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "session_id=SESSION_ID" \
  -F "chunk_index=0" \
  -F "chunk=@chunk_0.bin"

# Step 3: Complete the upload
curl -X POST http://127.0.0.1:8000/api/videos/upload/complete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"session_id": "SESSION_ID"}'
```

### Test 3: Video Streaming

```bash
# Stream video with Range header (progressive download)
curl -H "Range: bytes=0-1048575" \
  http://127.0.0.1:8000/api/videos/1/stream \
  -v

# Expected response headers:
# HTTP/1.1 206 Partial Content
# Content-Type: video/mp4
# Content-Length: 1048576
# Content-Range: bytes 0-1048575/524288000
```

## Frontend Integration

### Video Player Setup

```html
<video id="videoPlayer" controls>
  <source src="http://127.0.0.1:8000/api/videos/1/stream" type="video/mp4">
  Your browser does not support the video tag.
</video>
```

### JavaScript Streaming

The video streaming endpoint supports:
- **HTTP Range requests** for progressive download
- **Automatic view count** increment on stream access
- **Proper 206 Partial Content** responses for byte-range requests

## Troubleshooting

### Issue: Still getting 2MB limit

**Solution:** Ensure you're using the custom php.ini:

```bash
# Check if php.ini is being used
php -c php.ini -i | grep "Loaded Configuration File"

# Should show: Loaded Configuration File => /path/to/itechtube/php.ini
```

### Issue: Upload fails with "File exceeds upload_max_filesize"

**Solution:** Verify settings are applied:

```bash
# Restart the server after modifying php.ini
pkill -f "artisan serve"
./start-server.sh
```

### Issue: Streaming cuts off at 2MB

**Solution:** Check output_buffering setting:

```bash
php -c php.ini -i | grep output_buffering
# Should show: output_buffering => 0
```

### Issue: Memory exhausted during processing

**Solution:** Increase memory_limit or process videos in chunks.

## Performance Recommendations

1. **Use chunked uploads** for files larger than 100MB
2. **Enable HTTP caching** for video streaming (implement ETag headers)
3. **Consider using a CDN** for high-traffic video delivery
4. **Monitor server resources** during video processing
5. **Use appropriate chunk sizes:**
   - 1-5MB for stable connections
   - 5-10MB for fast connections
   - 10-50MB for very large files on local networks

## Related Documentation

- [API Documentation](API_DOCUMENTATION.md) - Full API reference
- [Thunder Client Testing Guide](THUNDER_CLIENT_TESTING_GUIDE.md) - API testing setup
- [Frontend Backend Integration](FRONTEND_BACKEND_INTEGRATION.md) - Frontend integration guide

## Summary

The 2MB response limit has been resolved by creating a custom `php.ini` configuration optimized for video streaming. The server now supports:

- ✅ **500MB** maximum video upload size
- ✅ **600MB** maximum POST size  
- ✅ **5 minute** maximum execution time
- ✅ **512MB** memory limit for processing
- ✅ Clean streaming without output buffering
- ✅ Chunked upload support for very large files

Restart your development server using `./start-server.sh` to apply these changes.

