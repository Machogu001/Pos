# Evidence Output Folder

Place generated audit artifacts in this folder.

## Start Here

- `CERTIFICATION_PACKET_INDEX.md`: consolidated packet index with status, evidence links, and remaining external requirement.

## Recommended outputs

- Security penetration test report and finding register
- Load benchmark raw JSON and summarized report
- Accessibility automated scan JSON and manual review report

## Suggested command outputs

- `node scripts/compliance/hrm-load-benchmark.js > docs/compliance/hrm/evidence/<date>_performance_<env>_hrm-load.json`
- `node scripts/compliance/hrm-a11y-audit.js > docs/compliance/hrm/evidence/<date>_accessibility_<env>_hrm-a11y.json`

## Integrity controls

- Include application commit hash in report metadata.
- Do not edit generated JSON manually; if needed, regenerate.
- Keep one folder per audit cycle if artifact volume grows.
