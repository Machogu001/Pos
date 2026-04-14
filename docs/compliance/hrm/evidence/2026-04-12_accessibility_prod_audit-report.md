# HRM Accessibility Audit Report

## 1. Audit Metadata

- Date: 2026-04-12
- Environment: production
- Application commit: 7df535359e6842f46f51389df7d74efd13188468
- Auditor: automated baseline script
- Standard target: WCAG 2.1 AA
- Raw data: `2026-04-12_accessibility_prod_hrm-a11y.json`

## 2. Pages/Flows Audited

- `/hrm`
- `/hrm/employees`
- `/hrm/attendances`
- `/hrm/leaves`
- `/hrm/payrolls`

## 3. Test Method

- Automated browser checks for:
  - form control labeling
  - image alt text
  - heading level continuity
  - link accessible names

## 4. Findings Summary

| ID | Severity | Page/Flow | WCAG Criterion | Issue | Evidence | Recommendation | Status |
|---|---|---|---|---|---|---|---|
| A11Y-BASE-001 | High | all tested paths | 1.3.1 / 3.3.2 (likely) | Form controls without associated labels (count 5) | JSON shows same finding on all paths | Re-run authenticated scan and remediate unlabeled controls | Open |

## 5. Important Limitation

- All audited HRM URLs are behind authentication and current run likely assessed login-flow DOM due redirects.
- This artifact is baseline-only and not sufficient as final certification evidence.

## 6. Exit Criteria to Close

- Run authenticated accessibility scan for in-scope HRM pages.
- Complete manual keyboard and screen-reader validation.
- Close all High/Critical findings.

## 7. Preliminary Conclusion

- Baseline automation is operational and producing repeatable output.
- Final accessibility certification requires authenticated execution and remediation confirmation.
