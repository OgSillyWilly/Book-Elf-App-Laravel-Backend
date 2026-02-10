#!/bin/bash

# Auto-restart Laravel server script
# This keeps the server running even if it crashes

cd /Users/silkedelvaux/Herd/showcase

echo "Starting Laravel server with auto-restart..."
echo "Server will be available at http://172.20.10.4:8000"
echo "Press Ctrl+C to stop completely"
echo ""

while true; do
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting server..."
    php artisan serve --host=0.0.0.0 --port=8000
    
    # If we get here, the server stopped
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Server stopped. Restarting in 2 seconds..."
    sleep 2
done
