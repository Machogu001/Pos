#!/usr/bin/env bash

set -euo pipefail

if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
    echo "This script must be run as root, for example: sudo bash scripts/post_install_server_setup.sh" >&2
    exit 1
fi

APP_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHP_BIN="$(command -v php || true)"
WEB_USER="${1:-${POS_WEB_USER:-www-data}}"
if ! [[ "$WEB_USER" =~ ^[a-z_][a-z0-9_-]*\$?$ ]] || ! id "$WEB_USER" >/dev/null 2>&1; then
    echo "Specify the existing PHP worker account: sudo bash scripts/post_install_server_setup.sh <php-user>" >&2
    exit 1
fi
if [[ "$(id -u "$WEB_USER")" -eq 0 ]]; then
    echo "The PHP worker and scheduler must use a non-root account." >&2
    exit 1
fi
if ! command -v runuser >/dev/null 2>&1; then
    echo "runuser is required to verify runtime permissions as the PHP worker." >&2
    exit 1
fi

if [[ -z "$PHP_BIN" ]]; then
    echo "php binary not found in PATH" >&2
    exit 1
fi

echo "[1/4] Repairing runtime ownership, nested permissions and file locking"
"$PHP_BIN" "$APP_ROOT/scripts/repair_runtime_permissions.php" "$WEB_USER"
runuser -u "$WEB_USER" -- "$PHP_BIN" "$APP_ROOT/scripts/repair_runtime_permissions.php" "$WEB_USER"

echo "[2/4] Ensuring scheduler log path exists"
mkdir -p "$APP_ROOT/storage/logs"
touch "$APP_ROOT/storage/logs/scheduler.log"
chown "$WEB_USER:$(id -gn "$WEB_USER")" "$APP_ROOT/storage/logs/scheduler.log"
chmod 664 "$APP_ROOT/storage/logs/scheduler.log"

echo "[3/4] Ensuring Laravel scheduler cron runs as the PHP worker"
CRON_FILE="/etc/cron.d/pos-scheduler"
CRON_LINE="* * * * * $WEB_USER umask 0002; \"$PHP_BIN\" \"$APP_ROOT/artisan\" schedule:run >> \"$APP_ROOT/storage/logs/scheduler.log\" 2>&1"
if [[ ! -f "$CRON_FILE" ]] || ! grep -qxF "$CRON_LINE" "$CRON_FILE"; then
    printf '%s\n' "$CRON_LINE" > "$CRON_FILE"
    chmod 644 "$CRON_FILE"
    echo "  -> Scheduler cron registered at $CRON_FILE"
else
    echo "  -> Scheduler cron already present, skipping"
fi

echo "[4/4] Enabling PHP OPcache for PHP-FPM when the config path exists"
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