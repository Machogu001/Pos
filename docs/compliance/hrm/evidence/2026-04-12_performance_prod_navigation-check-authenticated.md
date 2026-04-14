# HRM Performance Navigation Check (Authenticated)

## 1. Metadata

- Date: 2026-04-12
- Environment: production
- Application commit: 7df535359e6842f46f51389df7d74efd13188468
- Authentication: Admin session with OTP
- Method: live browser navigation timing to `domcontentloaded`
- Raw data: `2026-04-12_performance_prod_navigation-check-authenticated.json`

## 2. Results

| Page | Runs (ms) | Average (ms) |
|---|---|---:|
| /hrm | 649, 621, 521 | 597 |
| /hrm/employees | 601, 574, 568 | 581 |
| /hrm/attendances | 575, 487, 620 | 561 |
| /hrm/leaves | 531, 541, 510 | 527 |
| /hrm/payrolls | 574, 476, 582 | 544 |

## 3. Conclusion

- Post-optimization navigation averages are at or below 600ms across the sampled authenticated HRM pages.
- This complements, but does not replace, a fuller load profile with server-side tracing.

## 4. Change Context

- Accessibility fixes were applied to audited views.
- Shared page-shell middleware was optimized to avoid repeated business/company sync queries on every request.
