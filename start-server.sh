#!/bin/bash

# ItechTube Server Startup Script
# This script starts the Laravel server with proper PHP configuration for video uploads

echo "=========================================="
echo "  ItechTube Server Startup"
echo "=========================================="
echo ""

# Check if php.ini exists
if [ -f "php.ini" ]; then
    echo "✓ Custom php.ini found"
    echo "  - upload_max_filesize: $(grep 'upload_max_filesize' php.ini | cut -d= -f2)"
    echo "  - post_max_size: $(grep 'post_max_size' php.ini | cut -d= -f2)"
    echo "  - max_execution_time: $(grep 'max_execution_time' php.ini | cut -d= -f2)"
else
    echo "✗ Custom php.ini not found, using system defaults"
fi

echo ""

# Start the server with custom php.ini
if [ -f "php.ini" ]; then
    echo "Starting server with custom PHP configuration..."
    php -c php.ini artisan serve --host=127.0.0.1 --port=8000
else
    echo "Starting server with system defaults..."
    php artisan serve --host=127.0.0.1 --port=8000
fi

