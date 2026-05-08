#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

OUT_DIR="${ROOT}/storage/logs"
OUT_FILE="${OUT_DIR}/i18n_audit_$(date +%Y%m%d_%H%M%S).log"
mkdir -p "$OUT_DIR"

# Find suspicious hardcoded user-facing text in UI layers only (Blade + frontend JS).
# This keeps results actionable and avoids backend/business-logic noise.
rg -n --no-heading --color never \
  --glob '!vendor/**' \
  --glob '!node_modules/**' \
  --glob '!storage/**' \
  --glob '!bootstrap/cache/**' \
  --glob '!resources/plugins/**' \
  "(>\\s*[A-Za-z][^<{]{2,}<|\"[A-Za-z][^\"\\n]{2,}\"|'[A-Za-z][^'\\n]{2,}')" \
  resources/views Modules/**/Resources/views resources/js Modules/**/Resources/assets/js \
  | rg -v "@lang\(|__\(|trans\(|trans_choice\(|route\(|asset\(|config\(|class=|id=|type=|name=|placeholder=|aria-|data-|http-equiv|charset|viewport|csrf-token|icon-|fa-|tw-|svg|path d=|console\\.|localStorage|session\(|request\(|Auth::|Route::|Module::|action\(" \
  | tee "$OUT_FILE" >/dev/null

TOTAL=$(wc -l < "$OUT_FILE" | tr -d ' ')

echo "I18N audit complete."
echo "Report: $OUT_FILE"
echo "Potential hardcoded matches: $TOTAL"

echo
echo "Top 25 files by match count:"
awk -F: '{print $1}' "$OUT_FILE" | sort | uniq -c | sort -rn | head -25

if [[ "$TOTAL" -gt 0 ]]; then
  exit 1
fi
