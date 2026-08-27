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

# Install npm dependencies (leverages Docker layer cache)
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
    zip \
    curl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install pdo pdo_mysql pdo_sqlite gd mbstring zip \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer (official installer)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite (required by Laravel .htaccess)
RUN a2enmod rewrite

# ─────────────────────────────────────────────
#  Configure Apache: DocumentRoot -> /var/www/html/public
# ─────────────────────────────────────────────
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
  && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf /etc/apache2/sites-available/000-default.conf \
  && sed -ri -e "s/Options Indexes FollowSymLinks/Options -Indexes +FollowSymLinks/g" /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy compiled frontend assets from Stage 1
COPY --from=frontend-assets /build/public/build/ /var/www/html/public/build/

# Copy project files (everything in the frontend/ directory)
COPY . .

# Install PHP dependencies — production mode with optimized autoloader
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Create SQLite database file and ensure storage dirs are ready
RUN touch /var/www/html/database/database.sqlite \
  && chmod 644 /var/www/html/database/database.sqlite

# ─────────────────────────────────────────────
#  Set ownership and permissions
#  storage/       — log files, compiled views, session files
#  bootstrap/cache — framework cache
# ─────────────────────────────────────────────
RUN chown -R www-data:www-data \
      /var/www/html/storage \
      /var/www/html/bootstrap/cache \
      /var/www/html/database/database.sqlite \
  && find /var/www/html/storage -type f -exec chmod 644 {} \; \
  && find /var/www/html/storage -type d -exec chmod 755 {} \; \
  && find /var/www/html/bootstrap/cache -type f -exec chmod 644 {} \; \
  && find /var/www/html/bootstrap/cache -type d -exec chmod 755 {} \;

# Install startup script that handles Render's dynamic PORT
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Port exposed in the image (overridden at runtime by start.sh via $PORT)
EXPOSE 80

# Container start command — reads $PORT from Render and launches Apache
CMD ["/start.sh"]