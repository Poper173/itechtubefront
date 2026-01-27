#!/bin/bash

echo "========================================="
echo "Fixing Video Watching Process"
echo "========================================="

cd /home/prosper/itechtubefront

echo ""
echo "1. Running database migrations..."
php artisan migrate --force

echo ""
echo "2. Clearing cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear

echo ""
echo "3. Checking video storage directories..."
mkdir -p storage/app/public/videos
mkdir -p storage/app/public/thumbnails

echo ""
echo "4. Ensuring storage link exists..."
php artisan storage:link

echo ""
echo "5. Checking video files in database..."
php artisan tinker --execute="
use App\Models\Video;
\$videos = Video::all();
foreach (\$videos as \$video) {
    \$fileExists = file_exists(storage_path('app/public/' . \$video->file_path));
    echo \"Video #{\$video->id}: {\$video->title}\n\";
    echo \"  File path: {\$video->file_path}\n\";
    echo \"  File exists: \" . (\$fileExists ? 'YES' : 'NO') . \"\n\n\";
}
"

echo ""
echo "========================================="
echo "Setup Complete!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Make sure your video files are in: storage/app/public/videos/"
echo "2. Restart the server: php artisan serve"
echo "3. Test video playback"
echo ""
