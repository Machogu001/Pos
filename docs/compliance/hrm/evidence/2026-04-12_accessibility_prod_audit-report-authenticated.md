# HRM Accessibility Audit Report (Authenticated)

## 1. Audit Metadata

- Date: 2026-04-12
- Environment: production
- Application commit: 7df535359e6842f46f51389df7d74efd13188468
- Authentication: Admin session with OTP
- Standard target: WCAG 2.1 AA
- Raw data: `2026-04-12_accessibility_prod_hrm-a11y-authenticated.json`

## 2. Findings Summary

| ID | Severity | Page/Flow | Issue | Evidence | Status |
|---|---|---|---|---|---|
| A11Y-AUTH-001 | High | /hrm/employees | 1 unlabeled form control | Authenticated JSON output | Open |
| A11Y-AUTH-002 | High | /hrm/leaves | 1 unlabeled form control | Authenticated JSON output | Open |
| A11Y-AUTH-003 | High | /hrm/payrolls | 2 unlabeled form controls | Authenticated JSON output | Open |
| A11Y-AUTH-004 | Low | /hrm, /hrm/employees, /hrm/attendances, /hrm/leaves, /hrm/payrolls | Heading order skip detected | Authenticated JSON output | Open |

## 3. Conclusion

- Authenticated audit evidence is now captured against real HRM pages.
- HRM does not yet meet accessibility exit criteria because High-severity form-label issues remain open.

## 4. Required Follow-Up

1. Add explicit labels or aria-label/aria-labelledby to flagged controls in employees, leaves, and payroll pages.
2. Normalize heading hierarchy on HRM pages to avoid skipped heading levels.
3. Re-run authenticated accessibility audit after remediation.
