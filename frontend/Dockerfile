# ─────────────────────────────────────────────
#  Production Dockerfile for Laravel Frontend
#  Deploy: Render Web Service (Docker)
#  Base Image: php:8.2-apache
# ─────────────────────────────────────────────

# ───────────────
# Stage 1: Build frontend assets with Vite
# ───────────────
FROM node:20 AS frontend-assets

WORKDIR /build

# Install npm dependencies
COPY package.json package-lock.json* ./
RUN npm install

# Copy full project and build assets
COPY . .
RUN npm run build

# ───────────────
# Stage 2: Production PHP + Apache
# ───────────────
FROM php:8.2-apache

# Install system packages for PHP extensions and Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev \
    zip \
    curl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) pdo pdo_mysql pdo_sqlite gd mbstring zip \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache DocumentRoot -> /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
  && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf /etc/apache2/sites-available/000-default.conf \
  && sed -ri -e "s/Options Indexes FollowSymLinks/Options -Indexes +FollowSymLinks/g" /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copy compiled frontend assets from Stage 1
COPY --from=frontend-assets /build/public/build/ /var/www/html/public/build/

# Copy project files
COPY . .

# Ensure storage and cache directories exist with correct write permissions BEFORE composer install
RUN mkdir -p /var/www/html/storage/framework/views \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/cache \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
             /var/www/html/database \
  && touch /var/www/html/database/database.sqlite \
  && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Install PHP dependencies without running post-install scripts prematurely
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --prefer-dist

# Set final ownership for www-data
RUN chown -R www-data:www-data /var/www/html

# Install startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]