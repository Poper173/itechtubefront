# Video Streaming Fix - Missing Video Files

## Problem
The streaming endpoint `http://127.0.0.1:8000/api/videos/{id}/stream` returns 404 Not Found because the video files don't exist in the storage directory.

## Solution
You need to create the storage directories and add actual video files.

### Step 1: Create Storage Directories
Run these commands in your terminal:

```bash
cd ~/itechtube
mkdir -p storage/app/public/videos
mkdir -p storage/app/public/thumbnails
```

### Step 2: Create Symbolic Link
```bash
php artisan storage:link
```

### Step 3: Copy Your Video Files
Copy your `.avi` (or `.mp4`, `.mkv`, `.webm`) video files to the videos directory:

```bash
# Example - copy a video file
cp /path/to/your/video.avi storage/app/public/videos/

# Based on your database, you need these files:
# - 9ad1bdc4-550a-49a4-b489-babf74607eff.avi
# - 9bd8a9f5-14c1-46ec-9add-90b0e8528a92.avi
# - 251f4641-da7e-4b1d-8fa0-568a33b4a17a.avi
# - 033522b2-db82-425a-bfd2-0fd77bf82da4.avi
# - 8c329958-ce05-43c7-abd6-51751934e556.avi
# - 71ab0173-5aa0-45e2-9b77-e82202642e7a.avi
# - 21b8a6ff-ebe0-4c07-aab4-09de6ea1cdc3.avi
# - ad8f5431-0ff5-4600-9e89-8489b786ecce.avi
```

### Step 4: Copy Thumbnail Files
Copy thumbnail images to thumbnails directory:

```bash
# Based on your database, you need these files:
# - 56ed1468-bf4f-4482-86e6-1626b07eb55b.jpg
# - 31c5e3af-a9d7-42b4-bb49-6f3ac0400a68.jpg
# - 05736728-6273-4e6b-961d-183b5aff4f40.jpg
# - 818d3bdc-e70f-4c01-bd54-e52f37937e4f.jpg
# - db61c0af-0c73-49d2-b0d4-e5df82aae98c.jpg
# - 03b67845-32be-44a4-8215-c21b7d9d53b1.jpg
```

### Step 5: Restart Server
```bash
# Stop the current server (Ctrl+C) then restart:
php artisan serve
```

## Alternative: Use API to Upload Videos

Instead of manually copying files, you can use the upload feature:

1. Go to Dashboard: http://127.0.0.1:8000/frontend/dashboard.html
2. Login with your account
3. Click "Upload Video" button
4. Select your video file and fill in the details
5. Submit - the API will handle file storage automatically

## Check Existing Files
To verify files are in place:
```bash
ls -la storage/app/public/videos/
ls -la storage/app/public/thumbnails/
```

## Test Streaming
After adding files, test streaming:
```bash
curl -I http://127.0.0.1:8000/api/videos/10/stream
```

Should return HTTP 200 with video content headers instead of 404.
