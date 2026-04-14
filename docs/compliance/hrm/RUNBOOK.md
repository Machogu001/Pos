# HRM Compliance Runbook

This runbook defines how to generate and archive evidence required for HRM external audits and certifications.

## 1. Pre-Run Checklist

- Confirm target environment (staging or production shadow environment).
- Confirm release commit hash.
- Ensure HRM test suite is passing.
- Ensure authorized test account credentials are available.

## 2. Security Penetration Test Evidence

1. Copy template: `templates/penetration-test-plan.md`.
2. Fill scope, methodology, findings register, and sign-off sections.
3. Store final report in `evidence/` using naming convention.

### Authenticated Security Verification Command

`BASE_URL="https://your-host" COOKIE="<session cookies>" npm run compliance:hrm:security > docs/compliance/hrm/evidence/<date>_security_<env>_hrm-authenticated-verification.json`

Use this to generate engineering-side authenticated verification evidence before or alongside a formal external assessment.

## 3. Load Benchmark Evidence

### Command

`BASE_URL="https://your-host" COOKIE="<session cookies>" CONCURRENCY=10 ROUNDS=30 npm run compliance:hrm:load > docs/compliance/hrm/evidence/<date>_performance_<env>_hrm-load.json`

### Notes

- Use authenticated cookie for protected HRM routes.
- Run at least 3 times and record median run in the report template.

## 4. Accessibility Audit Evidence

### Command

`BASE_URL="https://your-host" npm run compliance:hrm:a11y > docs/compliance/hrm/evidence/<date>_accessibility_<env>_hrm-a11y.json`

### Notes

- Supplement automated output with manual keyboard and screen-reader checks.
- Record findings in `templates/accessibility-audit-report.md`.

## 5. Reporting and Sign-Off

- Fill `templates/load-benchmark-report.md` with measured metrics.
- Fill `templates/accessibility-audit-report.md` with issue list and status.
- Attach screenshots/log exports as supporting artifacts.
- Obtain Security and Engineering sign-off.

## 6. Exit Criteria

- Security: no open Critical/High findings.
- Performance: agreed p95 target met for HRM core endpoints.
- Accessibility: no open Critical/High issues on in-scope pages.
