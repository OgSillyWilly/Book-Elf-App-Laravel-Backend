#!/bin/sh
set -e

echo "Starting Book Elf API..."

# Ensure /data directory is writable by www-data
chown -R www-data:www-data /data

# Clear config cache to pick up environment variables
php artisan config:clear

# Cache config for better performance (now with runtime env vars)
php artisan config:cache

# Initialize database if it doesn't exist
if [ ! -f /data/database.sqlite ]; then
    echo "Database not found. Creating and initializing..."
    touch /data/database.sqlite
    chmod 664 /data/database.sqlite
    chown www-data:www-data /data/database.sqlite
    php artisan migrate --force
    echo "Database initialized successfully!"
else
    echo "Database already exists, skipping initialization."
fi

# Start PHP-FPM and Nginx
echo "Starting PHP-FPM and Nginx..."
php-fpm -D && nginx -g 'daemon off;'
