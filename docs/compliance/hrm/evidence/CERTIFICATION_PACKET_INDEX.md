# HRM Certification Packet Index

## Scope

- Environment: production
- Date: 2026-04-12
- Application commit: 7df535359e6842f46f51389df7d74efd13188468
- Module scope: HRM dashboard, employees, attendances, leaves, payroll, settings

## Executive Status

| Domain | Status | Evidence |
|---|---|---|
| Security (authenticated engineering verification) | Complete | `2026-04-12_security_prod_authenticated-verification.md` |
| Security (transport/session header snapshot) | Complete | `2026-04-12_security_prod_hrm-headers.txt` |
| Accessibility (authenticated, remediated) | Complete | `2026-04-12_accessibility_prod_audit-report-authenticated-remediated.md` |
| Performance (authenticated load sample) | Complete | `2026-04-12_performance_prod_load-report-authenticated.md` |
| Performance (authenticated navigation check) | Complete | `2026-04-12_performance_prod_navigation-check-authenticated.md` |
| Independent external assessor attestation | Pending external party | See "Remaining External Requirement" |

## Security Evidence

- Baseline report (updated): `2026-04-12_security_prod_pen-test-report.md`
- Authenticated verification report: `2026-04-12_security_prod_authenticated-verification.md`
- Authenticated verification raw JSON: `2026-04-12_security_prod_authenticated-verification.json`
- Authenticated session note (MFA/login flow): `2026-04-12_security_prod_authenticated-session-note.md`
- Header snapshot: `2026-04-12_security_prod_hrm-headers.txt`

## Accessibility Evidence

- Authenticated remediated report: `2026-04-12_accessibility_prod_audit-report-authenticated-remediated.md`
- Authenticated remediated raw JSON: `2026-04-12_accessibility_prod_hrm-a11y-authenticated-remediated.json`
- Historical pre-remediation authenticated report: `2026-04-12_accessibility_prod_audit-report-authenticated.md`
- Historical pre-remediation authenticated JSON: `2026-04-12_accessibility_prod_hrm-a11y-authenticated.json`

## Performance Evidence

- Authenticated load report: `2026-04-12_performance_prod_load-report-authenticated.md`
- Authenticated load raw JSON: `2026-04-12_performance_prod_hrm-load-authenticated.json`
- Authenticated navigation check report: `2026-04-12_performance_prod_navigation-check-authenticated.md`
- Authenticated navigation check raw JSON: `2026-04-12_performance_prod_navigation-check-authenticated.json`

## Reproducibility Commands

- Security verification:
  - `BASE_URL="https://pos.bremac.co.ke" COOKIE="<session cookies>" npm run compliance:hrm:security`
- Accessibility audit:
  - `BASE_URL="https://pos.bremac.co.ke" COOKIE="<session cookies>" npm run compliance:hrm:a11y`
- Load benchmark:
  - `BASE_URL="https://pos.bremac.co.ke" COOKIE="<session cookies>" npm run compliance:hrm:load`

## Remaining External Requirement

If your policy, regulator, or certifier requires independent attestation, attach a third-party authenticated penetration assessment report and sign-off to this packet.

Suggested naming:

- `<date>_security_prod_external-pen-test-report.pdf`
- `<date>_security_prod_external-pen-test-findings.csv`
- `<date>_security_prod_external-signoff.md`

## Sign-Off Checklist

- [x] Security engineering verification complete
- [x] Accessibility authenticated remediation verified
- [x] Performance authenticated evidence captured
- [x] Evidence files versioned in repository
- [ ] External assessor report attached (only if required by policy)
