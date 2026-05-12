#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

OUT_DIR="${ROOT}/storage/logs"
OUT_FILE="${OUT_DIR}/i18n_audit_$(date +%Y%m%d_%H%M%S).log"
mkdir -p "$OUT_DIR"

# Find suspicious hardcoded user-facing text in UI layers only.
# Split Blade markup and frontend JS scanning to reduce false positives from Blade expressions,
# SVG/path attributes, and already-translated templates.
{
  rg -n --no-heading --color never -P \
    --glob '!vendor/**' \
    --glob '!node_modules/**' \
    --glob '!storage/**' \
    --glob '!bootstrap/cache/**' \
    --glob '!resources/plugins/**' \
    "(>\\s*[A-Za-z][^<{@]{2,}<|(?:placeholder|title|aria-label)\\s*=\\s*[\"'][A-Za-z][^\"'\\n]{2,}[\"'])" \
    resources/views Modules/**/Resources/views \
    | rg -v "@lang\(|__\(|trans\(|trans_choice\(|<svg|</svg|<path|stroke=|fill=|viewBox=|xmlns=|d=|<!--|-->|@extends|@section|@if|@elseif|@foreach|@forelse|@php|@endphp|session\(|auth\(\)|route\(|asset\(|config\(|class=|id=|type=|name=|style=|data-|http-equiv|charset|viewport|csrf-token"

  rg -n --no-heading --color never -P \
    --glob '!vendor/**' \
    --glob '!node_modules/**' \
    --glob '!storage/**' \
    --glob '!bootstrap/cache/**' \
    --glob '!resources/plugins/**' \
    "[\"'][A-Za-z][^\"'\\n]{2,}[\"']" \
    resources/js Modules/**/Resources/assets/js resources/views Modules/**/Resources/views \
    | rg -v "@lang\(|__\(|trans\(|trans_choice\(|route\(|asset\(|config\(|class=|id=|type=|name=|style=|data-|http-equiv|charset|viewport|csrf-token|<svg|</svg|<path|stroke=|fill=|viewBox=|xmlns=|d=|console\\.|localStorage|session\(|request\(|Auth::|Route::|Module::|action\(|jquery|select2|datatable|DataTable|fa-|tw-|btn-|col-md-|modal-|form-control|input-group|checkbox|radio|@extends|@section|@if|@elseif|@foreach|@forelse|@php|@endphp"
} | tee "$OUT_FILE" >/dev/null

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
