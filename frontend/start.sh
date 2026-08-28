#!/bin/bash
# ─────────────────────────────────────────────
#  Render startup script for Laravel on Apache
#  Dynamically binds Apache to the $PORT env var
#  that Render assigns to each Web Service.
# ─────────────────────────────────────────────

set -e

# Render injects the container port via the PORT env variable
PORT="${PORT:-80}"

# ─── Bind Apache to the Render-assigned port ───
# Update the Listen directive in ports.conf
sed -i "s/^Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf

# Update the VirtualHost port in the site config
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# ─── Ensure the SQLite database exists ───
DB_FILE="/var/www/html/database/database.sqlite"
if [ ! -f "$DB_FILE" ]; then
    touch "$DB_FILE"
fi
chown -R www-data:www-data /var/www/html/database/
chmod 644 "$DB_FILE"

# ─── Ensure storage and cache are writable ───
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# ─── Clear Laravel config and cache ───
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# ─── Run database migrations (best-effort) ───
php artisan migrate --force 2>/dev/null || true

# ─── Start Apache in the foreground ───
exec apache2-foreground