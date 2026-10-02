# Mobile API

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

### Session
- `GET /me` — current user, business, permitted locations, permissions, and open register.
  - `permissions` keys used by the app's menu: `is_admin` (Admin role, opens the full website), `sell_create` (sell.create or direct_sell.access), `view_sales`, `view_products`, `view_customers` (customer.view or customer.view_own), `create_customer`, `view_dashboard`, `close_register` (close_cash_register), `edit_price`, `discount`.
  - Users who can sell may search products and customers even without product.view / customer.view.

### Dashboard
- `GET /dashboard?location_id=&period=today|week|month|custom&start_date=YYYY-MM-DD&end_date=YYYY-MM-DD` — sales, paid/due totals, expenses, net, and recent sales. `start_date`/`end_date` (inclusive) are required for `period=custom`.

### Products
- `GET /products?q=&location_id=&contact_id=&page=&per_page=20` — searchable sellable variations scoped to permitted locations.
- `GET /products/lookup?code=&location_id=&contact_id=` — exact SKU/barcode lookup.

Prices use the same rules as the web POS screen: location/customer selling price group, customer group markup, active product discounts, and inline tax (only when enabled for the business). `contact_id` is optional and defaults to the walk-in customer; pass the selected customer so cart prices match the server's sale pricing.

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

### M-Pesa
- `POST /mpesa/stk-push` — starts a sell STK push with `{ phone, amount, location_id }`.
- `GET /mpesa/status/{checkout_request_id}` — returns `pending`, `paid`, `failed`, or `cancelled`.

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

1. Ensure Laravel Passport is installed and keys/clients exist: `php artisan passport:install` (or `php artisan passport:keys` if clients already exist).
2. Serve the API only over HTTPS in production.
3. After deploying, clear cached framework state: `php artisan route:clear && php artisan config:clear`.
4. Confirm business locations, cash registers, M-Pesa credentials, and user permissions are configured before allowing mobile sales.
