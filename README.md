# Pos

Enhanced POS system based on Ultimate POS, with additional features for MPESA integrations, PWA install support, and accounting/reporting refinements.

## Installation & Setup

After cloning the repository, run the following commands to set up the system:

```bash
php artisan migrate
php artisan db:seed --class=AdminSettingsSeeder
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

These commands will:
- Create/update database tables (including custom MPESA/eTIMS fields)
- Initialize admin settings with default values
- Clear all caches for a fresh start

## Browser Installation Wizard

You can now install the system from the browser in a guided step-by-step flow.

Open:

```text
/install
```

If the app is not installed and a user visits the site, they are automatically
redirected into this installation wizard.

Wizard steps:
1. Instructions
2. Server Requirements check
3. Application + Database + Mail details
4. Automatic install (creates `.env`, runs migrations/seeds)
5. Final health-check summary step, then automatic redirect to Login

If the web server cannot write `.env` due to file permissions, the wizard
automatically shows a fallback screen with the exact `.env` content to paste,
then continues installation.

After successful installation, users are redirected to Login (with a Home
button available on the completion page).

After installation is complete, installer routes are automatically locked to
prevent accidental re-entry. Visiting `/install` or `/install/` again will
redirect to Login.

Legacy endpoint note: `/public/install/index.php` is kept only as a redirect
shim and now forwards to Login when installed, or to `/install-start` when not
installed.

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

### May 2026 — v12.5
- **Version bump to 12.5.** `config/author.php` updated; `released_at` and `update_check_url` config keys added.
- **Remote version notifications.** Public `/version-check` endpoint; `pos:fetchRemoteVersion` scheduled every 6 hours; update banner now surfaces both local-deploy and remote-update pending states with distinct messages.
- **Auto-close cash register.** Admin toggle in System Settings; `pos:autoCloseRegister` command closes open registers with sales at midnight (skips registers with no sales).

### January 2026
- Added dual MPESA credential support for subscriptions vs POS/sell payments (see DUAL_MPESA_CREDENTIALS.md).
- Implemented full PWA installation flow with working close/dismiss behaviour and telemetry (see PWA_* docs and pwa_test.js).
- Improved payment account mapping and reporting, including consistent linking of Sell payments to revenue accounts (e.g. Sales/Revenue - 5426425).
