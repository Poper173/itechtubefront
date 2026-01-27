#!/bin/bash
# Video Storage Fix Script
# Run this to set up video storage directories

echo "Creating storage directories..."
mkdir -p /home/prosper/itechtube/storage/app/public/videos
mkdir -p /home/prosper/itechtube/storage/app/public/thumbnails

echo "Creating symbolic link..."
cd /home/prosper/itechtube && php artisan storage:link

echo "Checking files..."
ls -la /home/prosper/itechtube/storage/app/public/videos/
ls -la /home/prosper/itechtube/storage/app/public/thumbnails/
ls -la /home/prosper/itechtube/public/storage

echo ""
echo "Done! Now copy your video files to:"
echo "  /home/prosper/itechtube/storage/app/public/videos/"
echo ""
echo "And copy thumbnail images to:"
echo "  /home/prosper/itechtube/storage/app/public/thumbnails/"
echo ""
echo "Then restart the server: php artisan serve"
