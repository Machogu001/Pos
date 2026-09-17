# Pos

Enhanced POS system branded as BreMac360, with additional features for MPESA integrations, PWA install support, and accounting/reporting refinements.

This deployment is owned and maintained by BreMac Consultant Limited.

For the full BreMac installation and maintenance runbook, see [INSTALLATION.md](/var/www/pos/INSTALLATION.md).

## Installation & Setup

BreMac supports two installation paths that now share the same backend install sequence.

### Command-Line Installation

1. Copy `.env.example` to `.env`.
2. Fill in at least `APP_NAME`, `ENVATO_PURCHASE_CODE`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, and `DB_USERNAME`.
3. Run the installer command from the project root:

```bash
php artisan pos:install --force
```

Use `php artisan pos:install --fresh --force` only when you intentionally want a full reinstall on an empty or disposable database.

The CLI installer runs the same supported sequence as the browser wizard:
- `php artisan pos:setup`
- core migrations and module migrations
- module asset publishing
- full database seeding
- Passport client installation
- permission cache reset and package discovery

### Browser Installation Wizard

If you prefer a guided setup, open `/install` in the browser and complete the wizard.

The browser wizard uses the same backend installer as `php artisan pos:install`, so both paths stay aligned.

If the web server cannot write `.env`, the wizard shows the exact `.env` content to paste manually and then resumes the same install flow after you save the file.

### Post-Install Server Steps

After either install path succeeds, run the required server-level production steps that the application cannot do for you:

```bash
sudo bash scripts/post_install_server_setup.sh
```

This helper script registers the scheduler cron entry, prepares the scheduler log file, and enables PHP OPcache for PHP-FPM when the system path exists.

The browser wizard still performs its final health checks and then redirects to login when installation is complete.

Installer routes are locked automatically after successful installation.

Legacy endpoint note: `/public/install/index.php` remains only as a redirect shim and forwards to Login when installed, or to `/install-start` when not installed.

## Production Deploy

This repository includes a production deploy script that automatically enforces
the superuser policy on every deploy.

Run on the production server from the project root:

```bash
bash scripts/deploy_production.sh
```

The script performs (in order):
- `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader`
- `php artisan migrate --force`
- `php artisan db:seed --class=SuperAdminSeeder --force`
- `php artisan admin:enforce-superuser-policy --force`  // auto-corrects admin role drift
- cache clear and rebuild steps

If you use an external CI/CD runner (GitHub Actions, GitLab, Jenkins, Forge, etc.),
call this same script in your deploy job to guarantee identical behavior in every
production release.

Deploy, update, and install flows also handle permission-cache reset failures more
gracefully so releases do not abort unnecessarily on environments with stricter
filesystem or cache permissions.

## MPESA Integrations

This project adds support for using separate MPESA credentials for subscription payments versus regular POS/sell payments.

- Subscriptions can use dedicated MPESA credentials stored in admin settings
- Regular POS/sell payments continue to use the default `.env` credentials
- Admin can configure subscription MPESA credentials from the dashboard

For full details and implementation notes, see:
- DUAL_MPESA_CREDENTIALS.md

## PWA (Progressive Web App)

The POS is installable as a PWA (desktop and mobile):

- Manifest, service worker, offline page, and icons are configured
- Installation prompt/"Install app" modal is enabled with proper dismiss handling
- HTTPS is required and supported in production

For setup, testing, and troubleshooting, see:
- PWA_INSTALLATION_SETUP.md
- PWA_INSTALLATION_CHECKLIST.md
- PWA_READY.md

You can also use the helper script:

```bash
node pwa_test.js https://your-pos-domain
```

## Accounting & Payment Accounts

The system supports mapping individual payments (including Sell and Purchase payments) to specific ledger accounts via the **Payment Account Report** screen in the UI.

- Payments can be linked to accounts like `Sales/Revenue - 5426425` or `Cost of Goods - 5426425`
- Use the "Link Account" action in the Payment Account Report to assign or change the account for a payment
- Chart of Account detail pages now render ledger rows from `account_transactions` by matching the chart `gl_code` to the operational account `account_number`
- Account detail responses disable browser caching and reset stale DataTables state to avoid empty Journal Entries tables after deploys or upgrades
- Ledger queries are schema-safe for installs where `account_transactions.deleted_at` does not exist
- Sell posting audit logs were reduced to actionable cases only: missing postings, successful repair, and repair failure

Any future schema or accounting-related changes should be accompanied by a brief note in this section or in a dedicated Markdown file.

## Auto-Close Cash Register

Registers can be automatically closed at midnight by the scheduler:

- Enable the toggle in **Admin Dashboard → System Settings → Cash Register**.
- Registers that had at least one sale that day are closed at 00:00 with note "Auto-closed by system at midnight."
- Registers with zero sales are left open (they were never used for selling).
- Run manually: `php artisan pos:autoCloseRegister`

## Remote Version Notifications

Every installation can poll a central update server for new releases:

1. Set `UPDATE_CHECK_URL=https://your-update-server/version-check` in `.env`.
2. The scheduler fetches this URL every 6 hours and caches the result.
3. If a newer version is detected, an amber banner appears for all users; superadmins see an "Apply Update" modal.
4. The public version endpoint on this server lives at `/version-check` (no auth required).

Run a manual check: `php artisan pos:fetchRemoteVersion`

## Changelog (Highlights)

### July 2026
- Stabilized Accounting account detail pages so Journal Entries reflect live ledger data from `account_transactions`, including deployment-safe handling for schemas without `deleted_at`.
- Cleared stale account-detail table state on the frontend to prevent empty tables caused by cached DataTables metadata after upgrades.
- Reduced sell-accounting reconciliation logs to actionable events only.

### June 2026
- Added POS bonus-item support for promotions such as buy X get Y free, with free quantities reducing stock and COGS without increasing the charged sell quantity.
- Removed redundant bonus-note receipt output and tightened accounting/reporting fixes around sell and purchase posting flows.
- Added purchase draft autosave and recovery, tightened restore so prompts only appear when real lines exist, and blocked duplicate purchase lines during keyed entry and draft restore while highlighting the existing row.
- Added stocktake autosave and restore so keyed stocktake lines can be recovered without losing entered values.
- Fixed logout/session handling so users are fully signed out instead of remaining effectively authenticated in the browser.
- Added stock-costing backfill controls, scheduler settings, and operational repair commands for missing accounting or costing layers.

### May 2026
- Added bank reconciliation audit logging plus PDF/Excel export support for reconciliation reporting.
- Hardened deploy, update, and installer flows so permission-cache reset problems are handled gracefully instead of breaking the release path.

### May 2026 — v12.5
- **Version bump to 12.5.** `config/author.php` updated; `released_at` and `update_check_url` config keys added.
- **Remote version notifications.** Public `/version-check` endpoint; `pos:fetchRemoteVersion` scheduled every 6 hours; update banner now surfaces both local-deploy and remote-update pending states with distinct messages.
- **Auto-close cash register.** Admin toggle in System Settings; `pos:autoCloseRegister` command closes open registers with sales at midnight (skips registers with no sales).

### January 2026
- Added dual MPESA credential support for subscriptions vs POS/sell payments (see DUAL_MPESA_CREDENTIALS.md).
- Implemented full PWA installation flow with working close/dismiss behaviour and telemetry (see PWA_* docs and pwa_test.js).
- Improved payment account mapping and reporting, including consistent linking of Sell payments to revenue accounts (e.g. Sales/Revenue - 5426425).
