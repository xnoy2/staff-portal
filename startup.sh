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

echo "==> Seeding default settings..."
php artisan db:seed --class=SettingSeeder --force

echo "==> Linking storage..."
php artisan storage:link 2>/dev/null || true

echo "==> Writing Reverb client config to storage..."
RKEY="${REVERB_APP_KEY:-}"
RHOST="${REVERB_HOST:-}"
RPORT="${REVERB_PORT:-443}"
RSCHEME="${REVERB_SCHEME:-https}"
echo "{\"key\":\"$RKEY\",\"host\":\"$RHOST\",\"port\":$RPORT,\"scheme\":\"$RSCHEME\"}" > storage/app/reverb.json
echo "    reverb.json written: key=$RKEY host=$RHOST"

echo "==> Starting server on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
