# HRM Load Benchmark Report (Authenticated)

## 1. Run Metadata

- Date: 2026-04-12
- Environment: production
- Application commit: 7df535359e6842f46f51389df7d74efd13188468
- Authentication: Admin session with OTP
- Raw data: `2026-04-12_performance_prod_hrm-load-authenticated.json`

## 2. Workload Profile

- Measurement source: authenticated browser context
- Duration model: 10 rounds x concurrency 5
- Endpoints: `/hrm`, `/hrm/employees`, `/hrm/attendances`, `/hrm/leaves`, `/hrm/payrolls`

## 3. SLO Targets

- p95 latency target: <= 600ms
- Error rate target: < 1%

## 4. Results Summary

| Endpoint | Requests | Error Rate | p50 (ms) | p95 (ms) | p99 (ms) | Notes |
|---|---:|---:|---:|---:|---:|---|
| /hrm | 50 | 0.00% | 984.30 | 1683.30 | 1934.40 | Authenticated HTTP 200 |
| /hrm/employees | 50 | 0.00% | 952.50 | 1671.50 | 1732.70 | Authenticated HTTP 200 |
| /hrm/attendances | 50 | 0.00% | 939.00 | 1731.50 | 1771.10 | Authenticated HTTP 200 |
| /hrm/leaves | 50 | 0.00% | 916.90 | 1600.20 | 1830.00 | Authenticated HTTP 200 |
| /hrm/payrolls | 50 | 0.00% | 878.40 | 1585.20 | 1677.20 | Authenticated HTTP 200 |

## 5. Conclusion

- Availability is acceptable in this run: 0% errors across all authenticated endpoints.
- Performance target is not met: all authenticated HRM endpoints exceed the baseline p95 target of 600ms.

## 6. Required Follow-Up

| Finding | Impact | Recommended Fix | Owner | Status |
|---|---|---|---|---|
| Authenticated p95 too high across HRM endpoints | Fails target response-time objective | Profile controller queries, DB indexes, joins, and repeated dashboard calculations | Engineering | Open |
