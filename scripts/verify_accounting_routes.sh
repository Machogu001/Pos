#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_ROOT"

if [[ ! -f artisan ]]; then
    echo "ERROR: artisan not found. Run this from the project workspace." >&2
    exit 1
fi

ROUTE_PATHS="$(php artisan route:list | awk '/^[[:space:]]*[A-Z|]+[[:space:]]+/ {print $2}')"

required_paths=(
    "accounting/dashboard"
    "accounting/dashboard/get_totals"
    "accounting/chart_of_account"
    "accounting/journal_entry"
    "accounting/transfers"
    "accounting/reconcile"
    "accounting/budget"
    "accounting/settings/detail_types"
    "accounting/transactions/sales"
    "accounting/install"
    "accounting/uninstall"
    "report/accounting"
    "report/accounting/trial_balance"
)

missing=0

echo "Checking critical Accounting routes..."
for path in "${required_paths[@]}"; do
    if grep -Fxq "$path" <<< "$ROUTE_PATHS"; then
        echo "  OK  - ${path}"
    else
        echo "  MISS- ${path}" >&2
        missing=1
    fi
done

if [[ "$missing" -ne 0 ]]; then
    echo "Accounting route health-check failed." >&2
    exit 1
fi

echo "Accounting route health-check passed."
