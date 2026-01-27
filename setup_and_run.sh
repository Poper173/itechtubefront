#!/bin/bash
# Complete Video Setup and Testing Script

cd /home/prosper/itechtube

echo "=== Step 1: Creating storage directories ==="
mkdir -p storage/app/public/videos
mkdir -p storage/app/public/thumbnails

echo "=== Step 2: Creating symbolic link ==="
php artisan storage:link

echo "=== Step 3: Creating sample video files ==="
VIDEOS=(
    "9ad1bdc4-550a-49a4-b489-babf74607eff.avi"
    "9bd8a9f5-14c1-46ec-9add-90b0e8528a92.avi"
    "251f4641-da7e-4b1d-8fa0-568a33b4a17a.avi"
    "033522b2-db82-425a-bfd2-0fd77bf82da4.avi"
    "8c329958-ce05-43c7-abd6-51751934e556.avi"
    "71ab0173-5aa0-45e2-9b77-e82202642e7a.avi"
    "21b8a6ff-ebe0-4c07-aab4-09de6ea1cdc3.avi"
    "ad8f5431-0ff5-4600-9e89-8489b786ecce.avi"
)

THUMBNAILS=(
    "56ed1468-bf4f-4482-86e6-1626b07eb55b.jpg"
    "31c5e3af-a9d7-42b4-bb49-6f3ac0400a68.jpg"
    "05736728-6273-4e6b-961d-183b5aff4f40.jpg"
    "818d3bdc-e70f-4c01-bd54-e52f37937e4f.jpg"
    "db61c0af-0c73-49d2-b0d4-e5df82aae98c.jpg"
    "03b67845-32be-44a4-8215-c21b7d9d53b1.jpg"
)

for video in "${VIDEOS[@]}"; do
    # Create minimal MP4-like file
    printf 'RIFF\x24\x00\x00\x00AVI LIST\x00\x00\x00\x00\x00\x00\x00' > "storage/app/public/videos/$video"
    dd if=/dev/zero bs=1024 count=50 2>/dev/null >> "storage/app/public/videos/$video"
done

for thumb in "${THUMBNAILS[@]}"; do
    # Create minimal JPEG file
    printf '\xff\xd8\xff\xe0\x00\x10JFIF\x00\x01\x01\x00\x00\x01' > "storage/app/public/thumbnails/$thumb"
    dd if=/dev/zero bs=1024 count=5 2>/dev/null >> "storage/app/public/thumbnails/$thumb"
done

echo ""
echo "=== Files created ==="
ls -la storage/app/public/videos/
ls -la storage/app/public/thumbnails/
ls -la public/storage

echo ""
echo "=== Testing streaming endpoint ==="
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/api/videos/10/stream

echo ""
echo ""
echo "=== Setup Complete! ==="
echo "Now restart your server: php artisan serve"
