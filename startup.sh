#!/bin/bash
set -e

echo "==> Removing dev artifacts..."
rm -f public/hot

echo "==> Preparing storage directories..."
mkdir -p storage/framework/views
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "==> Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:clear
php artisan view:cache

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Linking storage..."
php artisan storage:link 2>/dev/null || true

echo "==> Writing Reverb WebSocket client config..."
mkdir -p public/js
printf 'window.ReverbConfig={key:"%s",host:"%s",port:%d,scheme:"%s"};' \
    "${REVERB_APP_KEY:-}" \
    "${REVERB_HOST:-}" \
    "${REVERB_PORT:-443}" \
    "${REVERB_SCHEME:-https}" \
    > public/js/reverb-config.js
echo "    key=${REVERB_APP_KEY:-MISSING}, host=${REVERB_HOST:-MISSING}"

echo "==> Starting server on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
