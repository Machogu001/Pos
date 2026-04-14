# HRM Load Benchmark Report

## 1. Run Metadata

- Date: 2026-04-12
- Environment: production
- Application commit: 7df535359e6842f46f51389df7d74efd13188468
- Data set assumptions: production live data
- Tester: automated baseline script
- Raw data: `2026-04-12_performance_prod_hrm-load.json`

## 2. Workload Profile

- Duration model: 10 rounds x concurrency 5
- Warm-up: none (baseline quick pass)
- Concurrency: 5
- Endpoints: `/hrm`, `/hrm/employees`, `/hrm/attendances`, `/hrm/leaves`, `/hrm/payrolls`

## 3. SLO Targets

- p95 latency target: <= 600ms
- Error rate target: < 1%

## 4. Results Summary

| Endpoint | Requests | Error Rate | p50 (ms) | p95 (ms) | p99 (ms) | Notes |
|---|---:|---:|---:|---:|---:|---|
| /hrm | 50 | 0.00% | 287.11 | 514.17 | 575.90 | HTTP 302 redirect to login |
| /hrm/employees | 50 | 0.00% | 286.25 | 309.98 | 326.23 | HTTP 302 redirect to login |
| /hrm/attendances | 50 | 0.00% | 290.98 | 332.85 | 335.67 | HTTP 302 redirect to login |
| /hrm/leaves | 50 | 0.00% | 282.30 | 319.71 | 324.82 | HTTP 302 redirect to login |
| /hrm/payrolls | 50 | 0.00% | 302.77 | 334.97 | 368.61 | HTTP 302 redirect to login |

## 5. Resource Observations

- Application-level authenticated HRM performance not measured in this run because all requests were redirected to `/login`.
- Transport-level responsiveness meets baseline p95 target under unauthenticated redirect scenario.

## 6. Bottlenecks and Actions

| Finding | Impact | Recommended Fix | Owner | Target Date |
|---|---|---|---|---|
| Unauthenticated-only benchmark | Cannot certify real HRM page performance | Re-run benchmark with authenticated session cookie | DevOps/QA | Next audit run |

## 7. Preliminary Conclusion

- Baseline transport health is acceptable.
- Production certification evidence remains conditional until authenticated benchmark is executed and attached.
