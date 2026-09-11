#!/bin/sh
# Container entrypoint: prepare storage, migrate, warm the caches, then serve.
set -e

cd /var/www/html

export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf
mkdir -p /tmp/nginx-body /tmp/nginx-proxy /tmp/nginx-fastcgi

# storage/app/public is a mounted volume and starts out empty and root-owned.
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
         storage/logs storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "==> migrating"
php artisan migrate --force --seed --no-interaction || php artisan migrate --force --no-interaction

echo "==> linking storage"
php artisan storage:link --force || true
chown -R www-data:www-data storage/app/public 2>/dev/null || true

echo "==> caching config"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> starting php-fpm + nginx on :${PORT}"
php-fpm -D
exec nginx -g 'daemon off;'
