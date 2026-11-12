#!/usr/bin/env bash
set -euo pipefail

if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
    echo "Run this script with sudo so it can repair runtime ownership before deployment." >&2
    echo "Example: sudo bash scripts/deploy_as_web_user.sh www-data" >&2
    exit 1
fi

APP_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHP_BIN="$(command -v php || true)"
WEB_USER="${1:-${POS_WEB_USER:-www-data}}"
if [[ $# -gt 0 ]]; then
    shift
fi

if ! [[ "$WEB_USER" =~ ^[a-z_][a-z0-9_-]*\$?$ ]] || ! id "$WEB_USER" >/dev/null 2>&1; then
    echo "Specify the existing PHP worker account: sudo bash scripts/deploy_as_web_user.sh <php-user>" >&2
    exit 1
fi
if [[ "$(id -u "$WEB_USER")" -eq 0 ]]; then
    echo "The PHP worker must use a non-root account." >&2
    exit 1
fi
if [[ -z "$PHP_BIN" ]]; then
    echo "php binary not found in PATH" >&2
    exit 1
fi
if ! command -v runuser >/dev/null 2>&1; then
    echo "runuser is required to deploy as the PHP worker." >&2
    exit 1
fi

COMMAND="pos:deploy"
if [[ "${1:-}" == "--install" ]]; then
    COMMAND="pos:install"
    shift
elif [[ "${1:-}" == "--prepare" ]]; then
    COMMAND=""
    shift
fi

umask 0002
echo "Repairing runtime permissions for $WEB_USER..."
"$PHP_BIN" "$APP_ROOT/scripts/repair_runtime_permissions.php" "$WEB_USER"
runuser -u "$WEB_USER" -- env "POS_WEB_USER=$WEB_USER" "$PHP_BIN" "$APP_ROOT/scripts/repair_runtime_permissions.php" "$WEB_USER"

if [[ -z "$COMMAND" ]]; then
    echo "Runtime paths are ready for the web installation wizard."
    exit 0
fi

echo "Running $COMMAND as $WEB_USER..."
runuser -u "$WEB_USER" -- env "POS_WEB_USER=$WEB_USER" "$PHP_BIN" "$APP_ROOT/artisan" "$COMMAND" "$@"
