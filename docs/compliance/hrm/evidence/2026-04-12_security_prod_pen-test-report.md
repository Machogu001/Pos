# HRM Security Penetration Test Report (Baseline Kickoff)

## 1. Engagement Metadata

- Date: 2026-04-12
- Environment: production
- Application commit: 7df535359e6842f46f51389df7d74efd13188468
- Scope owner: HRM engineering
- Report type: baseline kickoff evidence

## 2. Scope

- `/hrm`
- `/hrm/employees*`
- `/hrm/attendances*`
- `/hrm/leaves*`
- `/hrm/payrolls*`
- `/hrm/settings*`

## 3. Baseline Evidence Captured

- Header snapshot file: `2026-04-12_security_prod_hrm-headers.txt`
- Observed response: HTTP 302 to `/login` on `/hrm` when unauthenticated.
- Session cookies set with `secure`, `httponly` (for session), and `samesite=lax` attributes.

## 4. Findings Register

| ID | Severity | Endpoint/Flow | Title | Evidence | Repro Steps | Fix Recommendation | Owner | Status |
|---|---|---|---|---|---|---|---|---|
| SEC-BASE-001 | Info | whole HRM scope | Authenticated engineering-side security verification completed | `2026-04-12_security_prod_authenticated-verification.md` and JSON evidence | Review authenticated route, CSRF, MFA, and session evidence | Retain for audit packet | Engineering | Closed |
| SEC-BASE-002 | Medium | governance/process | Independent third-party assessor sign-off not attached | No external assessor report in evidence pack | Engage external assessor if required by policy/certification program | Obtain independent sign-off and append report | Security team | Open |

## 5. Exit Criteria

- Full authenticated external penetration test completed.
- All Critical/High findings remediated or risk-accepted with sign-off.
- Retest evidence attached.

## 6. Preliminary Conclusion

- Baseline transport/session header evidence is captured.
- Authenticated engineering-side security verification evidence is now captured.
- The remaining open item is independent assessor evidence if your policy or certifier requires external sign-off.
