# HRM Compliance Evidence Pack

This folder contains process and certification artifacts for HRM quality assurance beyond unit/feature code correctness.

## Goals

- Produce repeatable evidence for security, performance, and accessibility controls.
- Keep audit artifacts versioned and traceable.
- Standardize report format for internal and external assessors.

## Structure

- `templates/`: report templates and checklists.
- `evidence/`: generated evidence files from each audit cycle.

## Auditor Entry Point

- `evidence/CERTIFICATION_PACKET_INDEX.md`: consolidated index for final certification packet review.

## Suggested Audit Cadence

- Security penetration test: quarterly, and before major HRM releases.
- Load benchmark: before release and after major query/controller changes.
- Accessibility audit: monthly and before release.

## Minimum Pass Criteria (Baseline)

- Security: no open Critical/High findings in production scope.
- Performance: p95 API response under 600ms at agreed target load for HRM core endpoints.
- Accessibility: no Critical/High accessibility issues in dashboard, payroll, attendance, leave, and employee flows.

## Artifact Naming Convention

Store generated files in `evidence/` using:

`<YYYY-MM-DD>_<domain>_<env>_<type>.<ext>`

Examples:

- `2026-04-12_security_prod_pen-test-report.md`
- `2026-04-12_performance_staging_load-report.json`
- `2026-04-12_accessibility_staging_dashboard-a11y.json`
