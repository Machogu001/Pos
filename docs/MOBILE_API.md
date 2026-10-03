# BreMac360 Mobile App and API Guide

The system has two mobile entry points: the installable website PWA and the
separate [BreMac360 Android app](https://github.com/Machogu001/pos_app).
The Android app combines native API screens with embedded website POS/admin
pages. Installing the PWA does not install or update the Android APK.

This guide describes Android app **2.12.1** (version code **17**), requiring
Android **8.0 (API 26)** or later. Both native and embedded operations require
connectivity; the Android app does not provide offline sales synchronization.

For server installation, see [the installation runbook](../INSTALLATION.md).
For app builds, signing and distribution, see the
[Android README](https://github.com/Machogu001/pos_app/blob/main/README.md).

## User guide

### Server address and sign-in

Enter the website base URL (for example, `https://example.com` or
`https://example.com/retail`) and tap **Save**. The app normalizes a pasted
`/api/mobile/v1` URL back to the website address. The editor stays hidden after
saving and sign-out; tap **Edit** to change it. Saving the URL is not a server
connectivity test.

Use the existing system username and password. The password eye reveals/hides
the entered value. If OTP is enabled, the app submits when six digits are
entered. **Verify and continue** remains available.

For SMS delivery, supported Google Play services can ask for consent to read
one arriving message, fill the code and submit it. The app does not require
general SMS-reading permission. Manual entry remains available for email,
declined consent, missing Play services, or messages that were not detected.
Resend by SMS/email requires a configured account target and the cooldown to
finish. An expired challenge requires starting sign-in again.

### Navigation, appearance and performance

The top-left drawer lists features permitted for the user. **Quick sale**,
sales history, products/stock, customers, cash-register actions and Home
performance use native API screens. **POS** opens the website's full POS inside
the app. Business admins also receive the available website menu, including
purchases, products and reports, filtered by permissions and enabled modules.
There is no separate Full system button.

Choose **Appearance > Light**, **Dark**, or **Use device theme**. This setting
survives sign-out; changing it returns to Home. Embedded pages are darkened
where supported by the phone's WebView, so website styling can differ.

Home performance supports **Today**, **Week**, **Month**, and **Custom**.
Custom uses an inclusive From/To date range and the app limits selection to
today or earlier. Pull down to refresh on supported screens. Left-to-right
swipes go back; right-to-left swipes restore the last eligible native screen
closed with Back, or use embedded page history. Edge gestures and horizontal
tables retain their own handling.

### Business locations and permissions

Use **Change location** in the drawer or **LOCATION** on Home when more than
one permitted active location is available.

| Selection | Effect |
|-----------|--------|
| Individual location | Native performance and sales history filter to that branch; branch-specific operations use it |
| All locations | Native performance, recent sales and sales history combine permitted locations only |
| Selling/stock while All locations is selected | Quick sale, products/stock, payments and registers keep the last selected individual branch, displayed in their app bar |
| Embedded website pages | Use the website page's own location controls, independent of the native filter |

The combined selection is remembered until sign-out. Changing the individual
branch from the drawer asks before clearing an unfinished quick-sale cart.
Quick sale also has a **SELLING FROM** selector; it selects a concrete branch
and reprices the cart. Never treat All locations as a location for a new sale
or register.

The server remains the authority for permissions and location access; hiding
a menu entry is not the access control. Own-sales-only permissions continue
to restrict combined sales history. Admin website access does not bypass
disabled modules or server authorization.

### Activity log

The system records native mobile-app and in-app website authentication
separately from ordinary browser authentication. Authorized users can review
the channel, device label and app version in **Reports > Activity log**.
Android device labels are maker/model descriptions; browser labels describe
the platform/type/browser, not a hardware serial number. These client-provided
labels must not be used as trusted device identity.

## API contract

Base URL: `{server}/api/mobile/v1`. All clients must send `Accept: application/json`; authenticated requests must send a Laravel Passport bearer token.

Responses use:

- Success: `{ "success": true, "data": ..., "meta": ... }`
- Error: `{ "success": false, "message": "...", "errors": {...}, "code": "..." }`

The Android app also sends `User-Agent: BreMac360App/<version> (Android <release>; <maker model>)` and `X-Device-Name: <maker model>`. Login/logout entries in **Reports → Activity log** then show "Signed in via" (BreMac360 mobile app, in-app website, or web browser), the device name and the app version.

## Endpoints

### Auth
- `POST /auth/login` — username/password login. Returns a Passport token or an OTP challenge when OTP is enabled.
- `POST /auth/otp/verify` — verifies the one-time code and returns a token.
- `POST /auth/otp/resend` — resends the OTP subject to cooldown.
- `POST /auth/logout` — revokes the current token.

Login accepts `{ username, password, device_name, otp_delivery_method? }`
(`sms` or `email`). Successful authentication returns
`data.status = "authenticated"` and `data.token`. An OTP challenge returns
`data.status = "otp_required"`, `otp_session`, delivery information, expiry and resend timing.
Verification accepts `{ otp_session, otp, device_name }`; resend accepts
`{ otp_session, otp_delivery_method? }`. Current challenge limits are five
minutes, five incorrect verification attempts and a 59-second resend cooldown.
Login, verification and resend additionally share the route's 10/minute
throttle.

### Session
- `GET /me` — current user, business, permitted locations, permissions, and open register.
  - `permissions` keys used by the app's menu: `is_admin` (Admin role, opens the full website), `sell_create` (sell.create or direct_sell.access), `view_sales`, `view_products`, `view_customers` (customer.view or customer.view_own), `create_customer`, `view_dashboard`, `close_register` (close_cash_register), `edit_price`, `discount`.
  - Users who can sell may search products and customers even without product.view / customer.view.

### Dashboard
- `GET /dashboard?location_id=&period=today|week|month|custom&start_date=YYYY-MM-DD&end_date=YYYY-MM-DD` — sales, paid/due totals, expenses, net, and recent sales. `start_date`/`end_date` (inclusive) are required for `period=custom`.

For dashboard and sales-history reads, omit `location_id` to combine the user's
permitted locations, or send an integer ID for a specific branch. Do not send
the literal `"all"`. The Android All locations option uses this existing API
behavior; no new aggregation endpoint is required.

### Products

- `GET /products?q=&location_id=&contact_id=&page=&per_page=20` — searchable sellable variations scoped to permitted locations.
- `GET /products/lookup?code=&location_id=&contact_id=` — exact SKU/barcode lookup.

Prices use the same rules as the web POS screen: location/customer selling price group, customer group markup, active product discounts, and inline tax (only when enabled for the business). `contact_id` is optional and defaults to the walk-in customer; pass the selected customer so cart prices match the server's sale pricing.

Product search/lookup, payment methods, sale creation, register opening and
M-Pesa STK requests require a concrete permitted integer `location_id`.
Products/stock are not aggregated by the Android All locations option.

### Customers

- `GET /customers?q=&page=` — active customer/both contacts.
- `POST /customers` — create a customer with `{ name, mobile, email? }`.

### Payment methods
- `GET /payment-methods?location_id=` — enabled methods from the POS utility.

### Cash register
- `GET /cash-register` — current open register or `null`.
- `POST /cash-register/open` — `{ location_id, opening_amount }`.
- `POST /cash-register/close` — `{ closing_amount, closing_note? }`. Requires `close_cash_register` (403 `forbidden` otherwise).

### Sales
- `POST /sales` — creates a POS sale from DB-priced variations, payments, and a required `client_reference` idempotency key. Error codes: `register_closed` (409), `insufficient_stock` (422), `subscription_expired` (403).
- `GET /sales?status=&location_id=&q=&page=` — paginated summaries.
- `GET /sales/{id}` — full sale details with receipt URL/text.
- `GET /sales/{id}/document` — authenticated PDF document envelope:
  `{ data: { filename, content_type: "application/pdf", content_base64 } }`.
  Uses the existing invoice PDF renderer and quotation headings, with explicit
  draft labeling. Requires business/location access and either sales-view
  permission or own-sale access (own-sales permission or sale-creation access).
  mPDF dependencies and writable `public/uploads/temp` must be available.
  Retained for older clients and separate website PDF downloads; Android
  v2.12.1 uses `/receipt` for sharing/opening as well as thermal printing.
- `GET /sales/{id}/receipt` — `{ data: { html } }`, the configured website
  receipt HTML with the same vendor/app stylesheets used by the guest invoice
  page. Reuses `SellPosController::receiptContent` in browser mode, including
  direct-sale layout selection, branding, item/tax/payment fields and footer.
  Same business/location/own-sale authorization as document access.
  Styles, logos and fonts must be served over HTTPS from the configured server
  origin (embedded data images/fonts are also allowed). Page scripts, frames,
  forms and cross-origin resources are not enabled in receipt preview.

New-sale and idempotent-retry responses include receipt text and the website
receipt URL. If URL generation fails after a sale was saved, `receipt_error`
reports the problem explicitly without treating the sale as unsaved.
The app opens the PDF inside the app and shares the PDF for completed and historical sales,
not plain text, and does not create a new sale or STK request to retrieve it.
M-Pesa payment references prefer the stored provider transaction number.

If sale completion reports an inability to create a lockable cache file,
the payment may already be confirmed: do not send another STK prompt.
Deploy the runtime permission repair and follow `INSTALLATION.md` to repair
existing cache ownership without clearing idempotency records. New installs
and supported deployments repair nested runtime permissions before cache
operations; insufficient OS privileges produce an explicit setup failure.

- `POST /sales/validate-stock` — `{ location_id, items: [{ variation_id, quantity }] }`.
  Returns `{ data: { items: [{ variation_id, enable_stock, stock }] } }`, or
  `insufficient_stock` (422) with the requested/available quantities.
  Requires sale-creation permission and permitted location access. This is a
  read-only stock check, not a reservation or payment.

The native app performs stock checks before checkout/payment/STK/completion.
Final mobile-sale creation rechecks totals per variation, including shared
stock components of combos, inside a transaction with stock locks. Final
mobile sales reject overselling even when the website allows it. Drafts and
quotations retain their non-stock-consuming behavior. An STK confirmation
does not reserve stock: if availability changes before completion, retain the
confirmed payment and resolve the stock/payment rather than sending STK again.

Android **Print receipt** supports paired Bluetooth Classic SPP or raw TCP
network ESC/POS printers, rendering the same website receipt template as a
58 mm (384-dot) or 80 mm (576-dot) monochrome raster image.
Printing is local to the phone and does not require a server print endpoint.
It fetches `/sales/{id}/receipt` before printing; no simplified text template
is used. Printers must support ESC/POS `GS v 0` raster images. Receipt viewing
and invoice viewing/sharing all use that HTML. Android v2.12.1 creates a
color, image-based PDF from the rendered 80 mm website layout, with one
receipt-sized page to avoid splitting rows. It does not substitute the
separate website download-PDF template. Thermal width, monochrome output and hardware resolution still
differ from A4/color output. Missing assets or oversized receipts fail rather
than silently truncate. See the Android README for pairing and limitations.

### M-Pesa
- `POST /mpesa/stk-push` — starts a sell STK push with `{ phone, amount, location_id }`.
- `GET /mpesa/status/{checkout_request_id}` — returns `pending`, `paid`, `failed`, or `cancelled`.

In Quick sale, use **Checkout > Add payment > M-Pesa**, enter the customer's
phone number and tap **Add**. The amount defaults to the checkout total or
remaining unpaid balance for split payments. After payment confirmation,
**Complete sale** saves the invoice. A failed receipt fetch or stock check is
not a reason to repeat a confirmed STK payment.

### Web POS
- `POST /web-session` with `{ target: "pos" | "home", path?: "/reports/profit-loss" }` — returns `{ url, expires_in }`, a single-use link (60 s) that signs the same user into the website and redirects to the POS screen (`pos`) or dashboard (`home`). For `home`, an optional local `path` opens that website page instead (absolute URLs, `//host` and login/logout paths are ignored). Requires `sell.create` or `direct_sell.access` for `pos`; `home` (full website) is limited to business admins (`permissions.is_admin`).
- `GET /web-menu` — admins only. Returns `{ items: [{ title, url, children: [{ title, url }] }] }`, the website's sidebar menu filtered by the user's permissions and enabled modules, so the app can list it in its navigation drawer.

### In-app website mode

The app's WebView adds `BreMac360App/<version>` to its User-Agent. Pages using `layouts.app` then
(`App\Utils\MobileAppView`): hide the website header (kept in the DOM so its modals and scripts work),
sidebar, footer and install prompt; force the mobile viewport; and expose the permission-filtered
sidebar menu as `window.__bremacAppMenu = [{ title, url, children: [{ title, url }] }]`, which the
app shows in its native navigation drawer.

## Setup notes

Use the supported installation/deploy workflow in
[INSTALLATION.md](../INSTALLATION.md), rather than reinstalling an existing
database to enable mobile access.

1. Deploy the mobile routes, controllers, authentication middleware and in-app
   website support together, before distributing the corresponding Android app.
2. Ensure Passport keys and a personal-access client exist. The supported
   installer sets up Passport; repair an existing installation only if needed.
   `php artisan passport:keys` creates keys when missing;
   `php artisan passport:client --personal` creates a missing personal-access
   client. Do not force key rotation as a routine update: it can invalidate
   existing tokens.
3. Serve the website and API over HTTPS with a trusted certificate. Ensure
   the proxy/web server forwards `Authorization`. The app also sends
   `X-Authorization` for hosts supported by the backend's fallback middleware;
   proxies must not drop both headers.
4. Keep the configured Laravel cache persistent between login, OTP verification
   and web-session redemption. Multi-node deployments need a shared cache for
   these challenges and single-use links.
5. Configure account SMS/email targets and delivery, active business locations,
   role permissions, subscription status, payment methods and registers.
   For M-Pesa, configure sell-payment credentials and working callback handling
   separately from subscription-payment credentials.
6. Use the deploy workflow's cache rebuild steps. When diagnosing stale routes
   or configuration, clear the relevant framework caches, then restore your
   production cache configuration. Restart PHP-FPM after code changes when
   OPcache timestamp validation is disabled.
7. Exercise sign-in/OTP, `/me`, location-limited and combined reads, a permitted
   sale/register flow, and embedded POS/admin navigation with representative
   admin and restricted accounts before rollout. Confirm forbidden locations
   remain inaccessible and activity entries identify the channel correctly.

## Troubleshooting and support

| Symptom | Administrator action |
|---------|----------------------|
| Server cannot be reached | Check base URL, certificate chain, connectivity, route deployment and proxy routing |
| OTP not delivered | Check account target, SMS/email provider settings and server delivery logs |
| SMS not auto-filled | Use manual entry; check Play services and message consent on the device |
| OTP challenge expired (`otp_expired`, 410) | Start sign-in again; check cache persistence, server time and attempt limits |
| Session expires immediately after successful OTP | Check Passport keys/personal-access client, API guard and Authorization forwarding |
| Forbidden (403) or hidden menu/location | Review permissions, allowed active locations, enabled modules and account restrictions |
| Generic server error | Inspect Laravel logs for the endpoint/action and underlying exception; a retry is not a configuration fix |
| Register closed (`register_closed`, 409) | Open the required register before completing the sale |
| Insufficient stock (422) | Review branch stock and requested quantities |
| STK push pending/failed | Review sell-payment credentials, callback processing and provider status before retrying |

Logs and support captures must not expose passwords, OTPs, bearer tokens,
single-use sign-in URLs, `.env` credentials or signing secrets.

## Release and maintenance

### v2.12.1 rollout checklist

Deploy this backend's stock-validation and document routes, controllers,
`MobileStockService` and PDF helper changes before distributing Android
v2.12.1, including `/sales/{id}/receipt` and the shared website receipt
renderer wrapper/view. Refresh route/config caches through the normal deployment workflow
and confirm the existing PDF dependencies and temp-directory permissions.
No database reset or key rotation is required.

Confirm that excess quantities and combined combo-component demand are rejected,
that completed and historical documents open/share without another payment,
and that intended users cannot retrieve another business's or forbidden
location's documents. Check paired Bluetooth/network printing on the actual
ESC/POS devices; an emulator cannot confirm paper output.

Backend and Android updates are separate deployments. Update the backend first,
then distribute the signed APK or publish the AAB through the chosen store.
PWA installation and server remote-version notifications do not update the
native Android binary automatically.

Keep app version numbers and this guide aligned with releases. Use the same
Android release signing key for direct-install upgrades and an increasing
version code. Keep signing material outside source control, with encrypted
backups and restricted access; the public repositories should contain neither
keystores nor passwords. See the Android README for build commands and artifact
paths. Local signed artifacts do not imply a GitHub Release or store publication.
