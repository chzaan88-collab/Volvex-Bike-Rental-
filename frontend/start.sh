#!/bin/bash
# ─────────────────────────────────────────────
#  Render startup script for Laravel on Apache
# ─────────────────────────────────────────────

set -e

# Render injects the container port via the PORT env variable
PORT="${PORT:-80}"

# ─── Bind Apache to the Render-assigned port ───
sed -i "s/^Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# ─── Ensure the SQLite database exists ───
DB_FILE="/var/www/html/database/database.sqlite"
if [ ! -f "$DB_FILE" ]; then
    touch "$DB_FILE"
fi
chown -R www-data:www-data /var/www/html/database/
chmod 666 "$DB_FILE"

# ─── Ensure storage and cache are writable ───
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# ─── Run database migrations FIRST (Creates cache table) ───
php artisan migrate --force || true

# ─── Clear Laravel config & route cache AFTER migration ───
php artisan config:clear
php artisan route:clear

# ─── Start Apache in the foreground ───
exec apache2-foreground