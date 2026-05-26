# Translation Quality Workflow

This project uses `php artisan translations:audit` to measure localization quality and enforce standards.

## Baseline-Aware CI

CI workflow: `.github/workflows/translation-audit.yml`

- Uses `docs/translation/translation_baseline.json` as baseline.
- Fails only when *new* translation issues are introduced.
- Publishes artifacts:
  - `storage/app/translation_audit_ci_new.json`
  - `storage/app/translation_worklists_ci/`

## Local Commands

### 1) Full audit export

```bash
php artisan translations:audit \
  --base=en \
  --export=storage/app/translation_audit.json
```

### 2) Generate translator worklists (top 200 per locale)

```bash
php artisan translations:audit \
  --base=en \
  --worklist-dir=storage/app/translation_worklists \
  --top-per-locale=200 \
  --export=storage/app/translation_audit.json
```

### 3) Target only high-impact file `ui.php`

```bash
php artisan translations:audit \
  --base=en \
  --files=ui.php \
  --worklist-dir=storage/app/translation_worklists_ui \
  --top-per-locale=200 \
  --export=storage/app/translation_audit_ui.json
```

### 4) Baseline-aware check (fail only on new issues)

```bash
php artisan translations:audit \
  --base=en \
  --baseline=docs/translation/translation_baseline.json \
  --new-only \
  --fail-on-issues \
  --export=storage/app/translation_audit_new_only.json
```

### 5) Refresh baseline after approved translation improvements

```bash
php artisan translations:audit \
  --base=en \
  --export=storage/app/translation_audit.json \
  --export-baseline=docs/translation/translation_baseline.json
```

## Notes

- `summary.total_issues` in JSON always reflects current full state per locale.
- In `--new-only` mode, `issues` are filtered to new issues only.
- Worklists are CSV files per locale for translation teams.
