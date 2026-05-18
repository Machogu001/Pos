# Service Level Agreement (SLA)
## BreMac POS / ERP System — v6.4

**System URL:** https://pos.bremac.co.ke  
**Effective Date:** May 18, 2026  
**Review Cycle:** Annually or on major version upgrade  

---

## 1. Scope

This SLA covers the **BreMac POS/ERP System** — a multi-module point-of-sale and enterprise resource planning platform serving BreMac Pharmacy. It applies to:

- All registered business users (Admins, Cashiers, Managers)
- All enabled modules: POS, Accounting, HRM, Inventory Management, Purchasing, CRM, Stocktake, Subscriptions, Asset Management, and related modules
- The production environment at `https://pos.bremac.co.ke`

---

## 2. Uptime Commitment

| Tier | Target | Max Allowed Downtime |
|------|--------|----------------------|
| **Production** | **99.9%** | ~8.7 hours / year · ~43 minutes / month |

### 2.1 Measurement
- Uptime is measured on a rolling **30-day calendar month**.
- Downtime is counted from the moment a confirmed outage is detected to when full service is restored.
- Scheduled maintenance windows (§6) are **excluded** from downtime calculations.

### 2.2 Exclusions
The following do not count against uptime:
- Scheduled maintenance windows with ≥24 hours advance notice
- Outages caused by third-party services (M-Pesa API, SMS gateway, ISP)
- Force majeure events (power grid failures, natural disasters)
- Outages caused by customer-initiated misconfigurations or unauthorized changes

---

## 3. Performance Targets

| Metric | Target |
|--------|--------|
| Page load (dashboard/home) | < 3 seconds (p95) |
| POS sale completion (submit) | < 2 seconds (p95) |
| Report generation (standard) | < 10 seconds |
| Report generation (large/export) | < 60 seconds |
| API response (M-Pesa callback) | < 5 seconds |
| Database query time (p99) | < 500 ms |

---

## 4. Incident Response & Resolution Times

### 4.1 Severity Definitions

| Severity | Description | Examples |
|----------|-------------|---------|
| **P1 — Critical** | System completely unavailable or data integrity at risk | Site down, login broken, payment processing failed for all users |
| **P2 — High** | Core feature broken for majority of users | POS cannot complete sales, reports returning errors |
| **P3 — Medium** | Non-critical feature broken or degraded | Single module inaccessible, slow reports, minor UI errors |
| **P4 — Low** | Cosmetic / informational issues | UI misalignment, non-blocking error messages |

### 4.2 Response & Resolution Targets

| Severity | Initial Response | Resolution Target |
|----------|-----------------|-------------------|
| P1 — Critical | **15 minutes** | **2 hours** |
| P2 — High | **1 hour** | **8 hours** (same business day) |
| P3 — Medium | **4 hours** | **48 hours** |
| P4 — Low | **1 business day** | **Next release cycle** |

> **Business hours:** Monday–Friday 08:00–18:00 EAT. P1 incidents are responded to 24/7.

---

## 5. Data & Backup Policy

| Item | Commitment |
|------|------------|
| **Automated backup frequency** | Daily (full database dump) |
| **Backup retention** | 30 days rolling |
| **Backup storage location** | Off-server (separate volume or remote storage) |
| **Recovery Point Objective (RPO)** | ≤ 24 hours (max data loss in worst-case recovery) |
| **Recovery Time Objective (RTO)** | ≤ 4 hours (time to restore from backup) |
| **Backup verification** | Test restore performed monthly |

### 5.1 Data Integrity
- All financial transactions are stored with double-entry accounting journal entries.
- M-Pesa payments are idempotent — duplicate callbacks do not create duplicate records.
- Soft-delete is used for critical records (contacts, products, transactions); data is never hard-deleted via normal operations.

---

## 6. Scheduled Maintenance Windows

| Window | Schedule |
|--------|----------|
| **Routine maintenance** | Sundays 01:00–03:00 EAT |
| **Major updates** | Announced ≥ 48 hours in advance |

- Maintenance notices will be communicated via the system's admin dashboard notification or SMS/email to registered admins.
- Emergency patches (security fixes) may be applied outside the window with best-effort notice.

---

## 7. Security Commitments

| Item | Commitment |
|------|------------|
| HTTPS/TLS | Enforced site-wide; HTTP redirected to HTTPS |
| Authentication | Session-based with role/permission enforcement (Spatie) |
| Passwords | Bcrypt-hashed; never stored in plaintext |
| M-Pesa credentials | Stored in `.env`; never exposed in logs or responses |
| Session timeout | Configurable per business; default idle timeout applies |
| Security patches | Applied within **7 days** of disclosure for critical CVEs |
| Dependency updates | Reviewed and applied on each major version cycle |

---

## 8. Support Channels

| Channel | Availability | Use For |
|---------|-------------|---------|
| System Admin (in-app) | Business hours | Configuration, user management |
| Email / WhatsApp | Business hours | P2–P4 incidents, feature questions |
| On-call phone | 24/7 | P1 — Critical incidents only |

---

## 9. Change Management

All changes to the production system follow this process:

1. **Code changes** — committed to `main` branch on GitHub (`github.com/Machogu001/Pos`)
2. **Deployment** — via `php artisan pos:deploy` (update) or `pos:deploy --fresh` (new install)
3. **Database migrations** — run automatically during deployment; no manual SQL
4. **Rollback plan** — git revert + `php artisan migrate:rollback` for schema changes; database backup restore for data issues

---

## 10. Internal IT Operations (Ops SLA)

### 10.1 Server Health Monitoring

| Check | Frequency | Action on Failure |
|-------|-----------|-------------------|
| HTTP health check (login page 200) | Every 5 minutes | Alert on 2 consecutive failures |
| Disk space | Hourly | Alert at 80%, escalate at 90% |
| MySQL replication lag (if applicable) | Every 5 minutes | Alert if > 30 seconds |
| Laravel error log scan | Every 15 minutes | Alert on new P1-class exceptions |
| Cron scheduler heartbeat | Every minute | Alert if `schedule:run` missed for > 5 minutes |

### 10.2 Deployment Runbook

**After git pull:**
```bash
cd /var/www/pos
git pull origin main
php artisan pos:deploy          # migrate, publish, seed perms, optimize
sudo systemctl reload apache2   # reload (not restart) to clear OPcache gracefully
```

**Fresh server install:**
```bash
# 1. Configure .env (DB, APP_KEY, M-Pesa credentials, etc.)
# 2. Run:
php artisan pos:deploy --fresh --force
# 3. Open browser → register business → roles are created automatically
```

**Emergency rollback:**
```bash
git revert <commit> && git push origin main
php artisan pos:deploy
```

### 10.3 Key Artisan Commands Reference

| Command | Purpose |
|---------|---------|
| `php artisan pos:deploy` | Full update deployment (non-destructive) |
| `php artisan pos:deploy --fresh --force` | Fresh install deployment |
| `php artisan pos:setup` | Re-run dir/symlink/key setup only |
| `php artisan queue:work` | Start queue worker |
| `php artisan schedule:run` | Run due scheduled jobs |
| `php artisan optimize:clear` | Clear all caches |
| `php artisan permission:cache-reset` | Reset Spatie permission cache |

---

## 11. SLA Credits (Customer-Facing)

If uptime falls below the 99.9% monthly target, affected users are eligible for service credits:

| Monthly Uptime | Credit |
|----------------|--------|
| 99.0% – 99.9% | 10% of monthly fee |
| 95.0% – 99.0% | 25% of monthly fee |
| < 95.0% | 50% of monthly fee |

Credits are applied to the next billing cycle. Credits are not provided for exclusions listed in §2.2.

---

## 12. Limitations of Liability

- Total liability for any single incident shall not exceed the monthly subscription fee paid for that month.
- The system provider is not liable for losses resulting from user error, misuse, or unauthorized access caused by inadequate password management.
- M-Pesa transaction failures caused by Safaricom's API are outside the scope of this SLA.

---

## 13. Review & Acceptance

| Role | Name | Signature | Date |
|------|------|-----------|------|
| System Administrator | | | |
| Business Owner | | | |
| IT Manager | | | |

---

*This document is version-controlled in the system repository at `SLA.md`.*
