# POS Installation Runbook

This system is owned and maintained by BreMac Consultant Limited.

## Supported Installation Paths

BreMac supports two installation paths, both backed by the same installer sequence:

1. Browser wizard at `/install`
2. Command-line installation with `php artisan pos:install --force`

Both paths run the same backend workflow through `App\Services\PosInstaller` so they stay aligned.

## Prerequisites

- PHP with the extensions required by the browser installer check: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `curl`, `zip`, `gd`
- MySQL or MariaDB database
- Writable `storage/` and `bootstrap/cache/`
- A populated `.env.example` file in the project root
- Shell access for production post-install steps such as cron and PHP-FPM reload

## Command-Line Installation

Use this path when you have shell access and want the most direct, repeatable install.

1. Copy `.env.example` to `.env`.
2. Fill in these required values before running the installer:
   `APP_NAME`, `ENVATO_PURCHASE_CODE`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`
3. From the project root run:

```bash
php artisan pos:install --force
```

Optional full reinstall for disposable or empty databases only:

```bash
php artisan pos:install --fresh --force
```

What the installer does:

- Runs `php artisan pos:setup --force`
- Repairs existing nested runtime/cache permissions before any cache operation;
  creates missing runtime directories and verifies writable, lockable files
- Clears stale cache manifests
- Runs core migrations
- Runs module migrations
- Publishes module assets
- Seeds core data
- Installs Passport clients
- Resets permission cache when possible
- Runs package discovery and optimize
- Stamps the installed app version in the database

## Browser Installation Wizard

Use this path when you want a guided install flow.

1. Open `/install`
2. Complete the server requirements check
3. Enter application, license, database, and email settings
4. Let the wizard generate `.env` and run the installation
5. Review the final health-check summary and continue to login

If the web server cannot write `.env`, the wizard shows the exact file content to paste manually, then resumes the same backend install flow after `.env` is saved.

Installer routes lock automatically after a successful installation to prevent accidental reuse.

## Production Post-Install Steps

The application cannot safely perform privileged server changes for you. Run these steps on the server after a successful install.

### Runtime permission repair

Browser installation, CLI installation, `pos:setup` and `pos:deploy` now
repair nested runtime directories/files automatically, including the hashed
`storage/framework/cache/data` paths used by mobile sale idempotency locks.
Unix directories use `2775` (setgid), files use `664`; private OAuth keys,
`.env` and application code are not made group-writable. File-cache writes
also use `664`. Symbolic links inside repaired runtime trees are refused.

For CLI commands, run as the PHP worker account or a deployment account in
its group and set `POS_WEB_USER` in `.env` to the actual worker username.
Cross-account Unix repair requires PHP's POSIX extension.
Root setup requires that username explicitly; it never guesses the account.
Processes cannot repair files owned by another account without OS permission.
Installation/deployment stops with an actionable error instead of claiming
success when repair fails.

For this existing production installation, after deploying the code:

```bash
cd /var/www/pos
sudo php scripts/repair_runtime_permissions.php www-data
sudo -u www-data php scripts/repair_runtime_permissions.php www-data
```

Replace `www-data` with the actual PHP worker account. This standalone repair
does not bootstrap Laravel, clear idempotency keys, delete cache files, or
create a sale/payment. It can therefore repair the permissions even when
Artisan cannot start. Do not use `chmod 777`. On Windows, filesystem ACLs
must permit the PHP process to write; Unix ownership/modes are not applied.

### 1. Register the Laravel scheduler

```bash
sudo bash scripts/post_install_server_setup.sh
```

The helper also repairs runtime ownership and verifies file locking as the
PHP worker before configuring cron. Pass a different worker account when
needed: `sudo bash scripts/post_install_server_setup.sh nginx`.
The scheduler uses that same account with a group-writable umask, avoiding
new root-owned cache files. Avoid running scheduled jobs as root.

### 2. Enable PHP OPcache for production

The helper script above also enables PHP OPcache for PHP-FPM when the expected system config path exists. If your server layout is non-standard, use the manual fallback below.

```bash
PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
sudo tee /etc/php/${PHP_VER}/fpm/conf.d/10-opcache.ini > /dev/null << 'EOF'
zend_extension=opcache.so
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.jit=off
EOF
sudo systemctl reload php${PHP_VER}-fpm
```

## Validation Checklist

After installation, confirm:

- `.env` exists in the project root
- `APP_KEY` is present
- Database connection works
- `users`, `business`, and `admin_settings` tables exist
- `storage/` is writable
- `bootstrap/cache/` is writable
- Login page loads
- Scheduler cron file exists in `/etc/cron.d/pos-scheduler`

## Maintenance Notes

- Use `php artisan pos:setup --force` for safe post-install repair of directories, symlinks, Passport keys, log files, and cache state.
- Use `php artisan pos:deploy --force` for normal code deploys.
- Avoid documenting raw `migrate` plus partial `db:seed` sequences as the primary install path, because that drifts away from the supported installer flow.
- If install behavior changes, update both this runbook and the browser success page messaging in the same change.

## Android App Deployment

The separate [BreMac360 Android app](https://github.com/Machogu001/pos_app)
uses this server's Mobile API and embedded website screens. It is not the
website PWA and requires its own signed APK installation or store distribution.
See [docs/MOBILE_API.md](docs/MOBILE_API.md) for the user guide, endpoint
contract, permission/location rules and troubleshooting.

Before distributing an app update:

- Deploy the compatible backend using the supported maintenance workflow.
- Confirm trusted HTTPS, mobile routes, Passport keys/personal-access client,
  and forwarding of authentication headers through the web server/proxy.
- Confirm the cache persists OTP challenges and single-use website sign-in
  links; use shared cache storage across application nodes.
- Configure account SMS/email delivery, business-location permissions, payment
  methods, cash registers and sell-payment M-Pesa credentials.
- Check login/OTP, restricted-account access, combined location reads and
  embedded website navigation against the deployed environment.

### Android v2.12.1: stock checks and unified website receipt documents

Deploy the mobile controller/routes, `MobileStockService` and invoice PDF helper
changes before installing the v2.12.1 APK. Required endpoints are:

- `POST /api/mobile/v1/sales/validate-stock`
- `GET /api/mobile/v1/sales/{id}/document`
- `GET /api/mobile/v1/sales/{id}/receipt`

Use the normal deployment workflow to refresh framework caches. Confirm mPDF
dependencies are installed and `public/uploads/temp` is writable by PHP.
This update does not require a database reset or Passport key rotation.

Final native mobile sales reject excess stock independently of the website's
Allow overselling setting. The app checks stock before checkout/payment/STK and
completion; the server rechecks during the save. Prechecks do not reserve stock.
If stock changes after M-Pesa confirmation, preserve the payment and resolve
the stock/payment rather than collecting it again.

Receipt preview, invoice opening/sharing and thermal printing use the website's configured receipt
template, including its direct-sale/branch layout selection, rather than an
app-specific text receipt. Serve its CSS/logo/font assets over trusted HTTPS
from the website's origin. Printers require ESC/POS raster-image support;
58/80 mm thermal output is monochrome and scaled to the chosen width.
Missing assets or oversized receipts should be resolved before printing.
Android v2.12.1 exports that rendered HTML at 80 mm width to a single-page,
receipt-sized color PDF for sharing/opening, rather than using the separate
mPDF download template. `/document` remains available for older clients.

PDFs open inside the Android app and can be shared as attachments. Existing
invoices, drafts and quotations can be retrieved without a new sale or payment.
Bluetooth Classic/network ESC/POS thermal printing runs locally on the phone;
no server printer service is required. Confirm actual printer compatibility,
Bluetooth permissions and trusted-network connectivity before rollout.

Native **All locations** combines performance and sales history only within
the user's permitted locations. Sales creation, products/stock, payments and
registers still require an individual branch. Embedded website pages retain
their own location filters.

The installer already handles Passport setup. Do not reinstall the database or
force regeneration of existing Passport keys for a normal app update. Android
signing keys are separate from Passport keys: preserve both securely, never
commit them, and use the existing Android release key for APK upgrades.

## BreMac Ownership

Operational ownership, deployment maintenance, and installation support for this system are handled by BreMac Consultant Limited.