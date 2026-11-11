# Microsoft Dynamics 365 Business Central integration

## Overview

BreMac POS can connect to Business Central 240 without changing normal POS behavior when the integration is disabled.

When enabled:

- BC owns products, customers, prices, blocked status, and inventory.
- POS synchronizes BC master data and stock continuously.
- POS sends finalized sales to BC and posts them as sales invoices.
- POS branches map to BC locations, ensuring sales reduce stock from the correct warehouse.

The current integration supports one BC company with multiple mapped BC locations.

## Enabling the integration

1. Sign in as superadmin.
2. Open **Operations Hub**.
3. Select the standalone **Dynamics 365 Business Central** card.
4. Enable **Business Central as inventory system of record**.
5. Select the POS business.
6. Enter the standard BC API URL and company GUID.
7. Enter the BreMac inventory API URL.
8. Configure authentication.
9. Enable each POS branch that should synchronize and enter its exact BC location code.
10. Select **Save and sync now**.

If the integration is disabled, the scheduler and sale listener skip BC processing and POS continues to work independently.

## Branch mapping example

| POS branch | BC location code | Result |
|---|---|---|
| Main | MAIN | Main sales and inventory use BC MAIN |
| XYZ Branch | XYZ | XYZ sales and inventory use BC XYZ |

The first synchronization resolves each location code to its BC location GUID. A finalized sale from Main sends that location GUID on every invoice line. When BC posts the invoice, stock is consumed from MAIN rather than another warehouse.

Do not enable a POS location until its BC location code is correct.

## Product synchronization

New BC items normally appear in POS within one minute.

| BC item value | POS product value |
|---|---|
| Number | SKU |
| Display name | Name |
| Base unit of measure | Unit |
| Unit cost | Purchase price |
| Unit price | Selling price |
| Blocked | Inactive |

BC items are created as single-variation POS products. Existing single products with the same SKU are updated. Variable products are not overwritten when their product SKU collides with a BC item number.

BC is authoritative. Later changes to the name, unit, cost, selling price, or blocked status overwrite the synchronized POS value.

## Customer synchronization

BC customers synchronize to POS customers. The BC customer number is stored in POS contact custom field 1 and is required when a POS sale is sent to BC.

Customer details synchronized include:

- Customer number and display name
- Tax registration number
- Address
- City, state, country, and postal code
- Phone and email
- Blocked/active status

## Inventory synchronization

Inventory is calculated separately for every enabled branch mapping.

The BreMac AL API returns open Item Ledger Entries for the requested BC location. POS sums remaining quantities by item and writes the result to the corresponding POS location.

Items that no longer have an open quantity in BC are reset to zero in the mapped POS branch, preventing stale stock.

## Sale posting

Only finalized `sell` transactions are synchronized. Subscription invoices are excluded.

For each sale:

1. POS locates the mapping for the transaction's POS location.
2. POS resolves the BC customer number from contact custom field 1.
3. POS creates a BC sales invoice using the POS invoice number as the external document number.
4. POS creates each invoice line using the item SKU/BC item number.
5. Each line receives the mapped BC `locationId`.
6. POS invokes the BC posting action.
7. BC creates the posted invoice and updates inventory for the mapped location.

An unmapped branch or missing customer/item number creates an explicit synchronization failure. POS does not silently use a default BC warehouse.

## Synchronization frequency

The integration command runs every minute through Laravel's scheduler:

```bash
php artisan business-central:sync
```

The command synchronizes:

1. Items
2. Customers
3. Inventory for every enabled branch mapping
4. Pending or retryable finalized sales

Use **Save and sync now** to run it immediately after changing configuration.

## Failure handling

POS sales remain saved if BC is temporarily unavailable. The integration records the failure and retries safe cases from the scheduler.

Duplicate protection includes:

- A persistent local-to-BC sale mapping
- POS invoice number as BC external document number
- A posted-invoice lookup before invoice creation
- No automatic HTTP retries for non-idempotent invoice POST requests
- Cleanup attempts for partially created draft invoices

## Operational checks

After enabling:

1. Create a test item in BC and confirm it appears in POS.
2. Add inventory to two BC locations and confirm each POS branch shows only its mapped quantity.
3. Synchronize a BC customer and use it in a POS test sale.
4. Finalize one sale in each POS branch.
5. Confirm each BC posted invoice line has the expected location.
6. Confirm BC inventory decreases only in that location.

## Troubleshooting

### Integration card is unavailable

Confirm the Business Central migrations have run and clear application/view caches.

### BC location cannot be resolved

Confirm the code in Operations Hub exactly matches the BC Location Code and that the integration account can read the standard `locations` API.

### New product does not appear

Confirm the scheduler is running, the item has a number, and the integration account can read items in the configured company.

### Sale remains failed

Confirm:

- The POS branch is mapped.
- The POS customer came from BC or has the correct BC customer number in custom field 1.
- Every sold SKU exists in BC.
- The integration account can create and post sales invoices.

Check `storage/logs/laravel.log` for the transaction ID and BC response.

## Technical deployment

See [the AL extension deployment guide](../integrations/business-central/README.md).
