#!/bin/sh
set -e

if [ -z "${APP_KEY}" ]; then
    echo "[entrypoint] ERROR: APP_KEY is not set. Set it in your environment." >&2
    exit 1
fi

echo "[entrypoint] Creating storage directories..."
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public

echo "[entrypoint] Caching config..."
php artisan config:cache

echo "[entrypoint] Caching views..."
php artisan view:cache

# Set RUN_MIGRATIONS=false on additional replicas to avoid concurrent migration attempts.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force
fi

echo "[entrypoint] Starting PHP-FPM..."
exec php-fpm
