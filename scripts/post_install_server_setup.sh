#!/usr/bin/env bash

set -euo pipefail

if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
    echo "This script must be run as root, for example: sudo bash scripts/post_install_server_setup.sh" >&2
    exit 1
fi

APP_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHP_BIN="$(command -v php || true)"

if [[ -z "$PHP_BIN" ]]; then
    echo "php binary not found in PATH" >&2
    exit 1
fi

echo "[1/3] Ensuring scheduler log path exists"
mkdir -p "$APP_ROOT/storage/logs"
touch "$APP_ROOT/storage/logs/scheduler.log"
chmod 664 "$APP_ROOT/storage/logs/scheduler.log" || true

echo "[2/3] Ensuring Laravel scheduler cron entry exists"
CRON_FILE="/etc/cron.d/pos-scheduler"
CRON_LINE="* * * * * www-data $PHP_BIN $APP_ROOT/artisan schedule:run >> $APP_ROOT/storage/logs/scheduler.log 2>&1"
if [[ ! -f "$CRON_FILE" ]] || ! grep -qF "artisan schedule:run" "$CRON_FILE"; then
    printf '%s\n' "$CRON_LINE" > "$CRON_FILE"
    chmod 644 "$CRON_FILE"
    echo "  -> Scheduler cron registered at $CRON_FILE"
else
    echo "  -> Scheduler cron already present, skipping"
fi

echo "[3/3] Enabling PHP OPcache for PHP-FPM when the config path exists"
PHP_VER="$($PHP_BIN -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null)"
OPCACHE_INI_PATH="/etc/php/${PHP_VER}/fpm/conf.d/10-opcache.ini"
if [[ -f "$OPCACHE_INI_PATH" ]]; then
    if ! grep -q '^opcache.enable=1' "$OPCACHE_INI_PATH"; then
        cat > "$OPCACHE_INI_PATH" <<'OPCACHE'
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
        echo "  -> OPcache configured"
    else
        echo "  -> OPcache already configured, skipping"
    fi

    systemctl reload "php${PHP_VER}-fpm" 2>/dev/null || service "php${PHP_VER}-fpm" reload 2>/dev/null || true
else
    echo "  -> PHP-FPM OPcache config path not found at $OPCACHE_INI_PATH, skipping"
fi

echo "Server setup complete"