# Dockerfile for Laravel API on Fly.io
# Using official PHP 8.4 FPM + Nginx

FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    sqlite3 \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies (without scripts, they need artisan file)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

# Copy application files
COPY . .

# Run composer scripts now that artisan exists
RUN composer run-script post-autoload-dump

# Create necessary directories and set permissions
RUN mkdir -p \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    /var/www/html/database \
    && chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
        /var/www/html/database

# Configure nginx
RUN rm -f /etc/nginx/sites-enabled/default
COPY <<'EOF' /etc/nginx/sites-available/laravel
server {
    listen 8080;
    server_name _;
    root /var/www/html/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

RUN ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/laravel

# Copy and set up entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 8080
EXPOSE 8080

# Use entrypoint script to initialize database and start services
CMD ["/usr/local/bin/docker-entrypoint.sh"]
