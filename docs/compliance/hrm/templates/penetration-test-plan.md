# HRM Penetration Test Plan (Template)

## 1. Engagement Metadata

- Date:
- Environment:
- Tester(s):
- Application version/commit:
- Scope owner:
- Approver:

## 2. Scope

### In-Scope Components

- HRM dashboard (`/hrm`)
- Employees (`/hrm/employees*`)
- Attendance (`/hrm/attendances*`)
- Payroll (`/hrm/payrolls*`)
- Leaves and leave types (`/hrm/leaves*`, `/hrm/leave_types*`)
- HRM settings (`/hrm/settings*`)

### Out-of-Scope Components

- Third-party hosted services (unless explicitly listed)
- Infrastructure layer (unless explicitly listed)

## 3. Test Methodology

Align with:

- OWASP ASVS (latest stable)
- OWASP Top 10 (latest stable)
- PTES/NIST-aligned internal checklist

### Required Security Checks

- Authentication/session handling
- Authorization and privilege escalation
- Input validation and injection (SQLi, XSS, command injection)
- CSRF and state-changing action protection
- File upload/download and path traversal risks
- Sensitive data exposure and error handling
- Logging/auditing integrity
- Business-logic abuse (leave approval/payroll manipulation)

## 4. Findings Register

| ID | Severity | Endpoint/Flow | Title | Evidence | Repro Steps | Fix Recommendation | Owner | Status |
|---|---|---|---|---|---|---|---|---|
| SEC-001 |  |  |  |  |  |  |  |  |

## 5. Severity Model

- Critical: immediate exploitation with major impact.
- High: practical exploitation with significant impact.
- Medium: meaningful risk requiring planned remediation.
- Low: minor risk or hard-to-exploit condition.

## 6. Exit Criteria

- All Critical/High remediated or accepted with formal risk sign-off.
- Medium findings have owners and due dates.
- Retest confirms fixes for remediated issues.

## 7. Sign-Off

- Security Lead:
- Engineering Lead:
- Product Owner:
