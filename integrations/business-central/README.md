# Business Central 240 integration deployment

This directory contains the AL extension required by the BreMac POS integration with Microsoft Dynamics 365 Business Central 240.

For day-to-day configuration and operating behavior, see [the Business Central administrator guide](../../docs/BUSINESS_CENTRAL.md).

## Architecture

The integration uses:

- Business Central standard API v2.0 for items, customers, locations, sales invoices, sales invoice lines, and invoice posting.
- The included BreMac custom API for location-filtered open Item Ledger Entries.
- Laravel's scheduler for continuous synchronization.
- Persistent POS-to-BC entity and location mappings for idempotency and branch-safe stock synchronization.

BC remains the system of record for product master data, customers, and physical inventory. POS remains the point where cashiers finalize sales.

## Included AL objects

The `BreMacPOS` AL project contains:

- `BreMac POS Inventory Entries` API page
- `BreMac POS Integration` codeunit
- `BreMac POS API` permission set

The inventory API exposes open Item Ledger Entries with item number, location code, and remaining quantity. POS aggregates those entries by item and mapped location.

## Build and publish the extension

1. Open `BreMacPOS` in Visual Studio Code with the Microsoft AL Language extension.
2. Create or update `.vscode/launch.json` for the BC240 server instance.
3. Download symbols.
4. Build the extension.
5. Publish and install the generated `.app` package.
6. Assign the `BreMac POS API` permission set to the integration account.

The integration account also needs standard API permissions to:

- Read companies, locations, items, customers, and posted sales invoices.
- Create and delete draft sales invoices.
- Create sales invoice lines.
- Post sales invoices.

## API URLs

Standard API:

```text
https://SERVER:PORT/BC240/api/v2.0
```

BreMac inventory API:

```text
https://SERVER:PORT/BC240/api/bremac/pos/v1.0/companies({company_id})/posInventoryEntries
```

Keep the literal `{company_id}` placeholder in the inventory URL. POS replaces it with the configured company GUID.

## Authentication

Operations Hub supports:

- Basic authentication using a BC username and web service access key/password.
- OAuth 2.0 client credentials using a token URL, client ID, client secret, and scope.
- A pre-issued bearer token.

Credentials are encrypted in the POS database and are not returned in settings JSON responses. Leave the secret field blank when editing settings to preserve the existing secret.

## Scheduler

The scheduler must run every minute:

```cron
* * * * * cd /path/to/pos && php artisan schedule:run >> /dev/null 2>&1
```

Manual synchronization:

```bash
php artisan business-central:sync
```

The command exits successfully without contacting BC when the integration is disabled.

## Data mappings

| Business Central | POS |
|---|---|
| Item number | Product SKU and variation sub-SKU |
| Item display name | Product name |
| Base unit of measure | Product unit |
| Unit cost | Default purchase price |
| Unit price | Default selling price |
| Blocked item | Inactive product |
| Customer number | Contact custom field 1 and contact ID |
| Customer display name and details | POS customer |
| Location GUID/code | Configured POS branch/location mapping |
| Remaining Item Ledger Entry quantity | POS branch quantity available |
| POS invoice number | BC external document number |

## Location behavior

One configured BC company can contain multiple BC locations. Each enabled POS branch maps to exactly one BC location code.

During the first sync, POS resolves the BC location code through the standard `locations` API and stores its GUID. If the administrator changes the location code, the stored GUID is cleared and resolved again.

When POS posts a sale, every invoice line includes the resolved BC `locationId`. Posting the invoice therefore consumes inventory from the BC location mapped to the POS branch that created the transaction.

Inventory synchronization runs independently for every enabled mapping. Before applying the BC result, POS resets mapped-product quantities for that POS branch to zero, then applies the quantities returned for the mapped BC location. This ensures items that reach zero in BC do not retain stale POS stock.

## Sale idempotency and failures

- POS records a synchronization mapping before sending a finalized sale.
- The POS invoice number is sent as BC's external document number.
- Before creating an invoice, POS checks posted invoices for the same external document number.
- Non-idempotent POST requests are not automatically retried.
- If line creation fails, POS attempts to remove the incomplete BC draft.
- Unmapped POS branches fail explicitly instead of using the wrong BC location.
- Failed sales remain recorded in POS and are retried by the scheduler when it is safe to do so.

## Deployment

Run migrations after deploying the PHP changes:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan view:cache
```

Required migrations create:

- Business Central configuration columns on `admin_settings`
- `business_central_mappings`
- `business_central_location_mappings`

## Troubleshooting

### New BC items do not appear in POS

- Confirm the integration is enabled.
- Run `php artisan business-central:sync` and inspect its output.
- Confirm the scheduler is running.
- Confirm the integration account can read the standard `items` API.
- Confirm the item has a number and is in the configured BC company.

### Inventory is wrong for a branch

- Confirm that the POS branch is enabled in the mapping table.
- Confirm the BC location code exactly matches BC.
- Confirm the inventory API URL uses the BreMac endpoint.
- Check that Item Ledger Entries use the expected BC location code.
- Use **Save and sync now** after correcting a mapping.

### A sale is not posted

- Confirm the sale is final and is not a subscription invoice.
- Confirm its POS branch has an enabled BC mapping.
- Confirm the customer has a BC customer number in POS contact custom field 1.
- Confirm every sold item SKU exists as a BC item number.
- Check `storage/logs/laravel.log` and the Operations Hub last synchronization error.
