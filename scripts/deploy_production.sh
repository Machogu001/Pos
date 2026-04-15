#!/usr/bin/env bash
set -euo pipefail

# Production deploy script for POS.
# Run from project root on the target server.

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_ROOT"

echo "[1/8] Installing/updating PHP dependencies"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

echo "[2/8] Running database migrations"
php artisan migrate --force

echo "[3/8] Ensuring superadmin baseline seeder runs"
php artisan db:seed --class=SuperAdminSeeder --force

echo "[4/8] Enforcing superuser policy (auto-correct role drift)"
php artisan admin:enforce-superuser-policy --force

echo "[5/8] Clearing old caches"
php artisan optimize:clear

echo "[6/8] Rebuilding config cache"
php artisan config:cache

echo "[7/8] Rebuilding view cache"
php artisan view:cache || true

echo "[8/8] Deploy complete"
