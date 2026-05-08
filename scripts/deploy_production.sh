#!/usr/bin/env bash
set -euo pipefail

# Production deploy script for POS.
# Run from project root on the target server.

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_ROOT"

echo "[1/9] Installing/updating PHP dependencies"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

echo "[2/9] Running database migrations"
php artisan migrate --force

echo "[3/9] Ensuring superadmin baseline seeder runs"
php artisan db:seed --class=SuperAdminSeeder --force

echo "[4/9] Enforcing superuser policy (auto-correct role drift)"
php artisan admin:enforce-superuser-policy --force

echo "[5/9] Clearing old caches"
php artisan optimize:clear

echo "[6/9] Rebuilding production caches (config, routes, views)"
php artisan optimize
php artisan view:cache || true

echo "[7/9] Verifying critical Accounting routes"
bash "$APP_ROOT/scripts/verify_accounting_routes.sh"

echo "[8/9] Ensuring Laravel scheduler cron entry exists"
CRON_FILE="/etc/cron.d/pos-scheduler"
CRON_LINE="* * * * * www-data /usr/bin/php ${APP_ROOT}/artisan schedule:run >> ${APP_ROOT}/storage/logs/scheduler.log 2>&1"
if [ ! -f "$CRON_FILE" ] || ! grep -qF "artisan schedule:run" "$CRON_FILE"; then
    echo "$CRON_LINE" | sudo tee "$CRON_FILE" > /dev/null
    sudo chmod 644 "$CRON_FILE"
    echo "  -> Scheduler cron registered at $CRON_FILE"
else
    echo "  -> Scheduler cron already present, skipping"
fi

echo "[9/9] Enabling PHP OPcache for PHP-FPM (if not already enabled)"
OPCACHE_INI=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null)
OPCACHE_INI_PATH="/etc/php/${OPCACHE_INI}/fpm/conf.d/10-opcache.ini"
if [ -f "$OPCACHE_INI_PATH" ] && ! grep -q "^opcache.enable=1" "$OPCACHE_INI_PATH"; then
    sudo tee "$OPCACHE_INI_PATH" > /dev/null << 'OPCACHE'
; configuration for php opcache module
; priority=10
zend_extension=opcache.so
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.jit=off
OPCACHE
    echo "  -> OPcache enabled. Reloading PHP-FPM..."
    sudo systemctl reload "php${OPCACHE_INI}-fpm" 2>/dev/null || sudo service "php${OPCACHE_INI}-fpm" reload 2>/dev/null || true
else
    echo "  -> OPcache already configured, skipping"
fi

echo "Deploy complete"
