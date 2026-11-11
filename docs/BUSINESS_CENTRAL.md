# Microsoft Dynamics 365 Business Central integration

## Overview

BreMac POS can connect to Business Central 240 without changing normal POS behavior when the integration is disabled.

When enabled:

- BC owns products, customers, vendors, salespeople, prices, blocked status, and inventory.
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
10. Select **Save and sync now** to import BC master data.
11. Reopen the card and map every POS user who makes sales to an active BC salesperson.
12. Save the settings again.

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

All BC customers synchronize to POS customers. The BC customer number is stored in POS contact custom field 1 and in the integration mapping used when a POS sale is sent to BC.

Customer details synchronized include:

- Customer number and display name
- Tax registration number
- Address
- City, state, country, and postal code
- Phone and email
- Blocked/active status

## Vendor synchronization

All BC vendors synchronize one way from BC to POS suppliers. Vendor number, display name, tax number, address, phone, email, and blocked status are updated on each synchronization. POS does not create or update vendors in BC.

Customer and vendor mappings are stored separately, so a BC customer number and BC vendor number can be tracked independently.

## Salesperson mapping

The standard BC `salespeoplePurchasers` API supplies the salesperson list. Synchronization does not create POS login accounts and does not create salespeople in BC.

In Operations Hub, map each active POS user to one active BC salesperson code. A BC salesperson can be assigned to only one POS user. The mapping is stored by BC record ID and code rather than relying on a potentially duplicated display name.

If a POS user is not mapped, or the selected salesperson is blocked or no longer available in BC:

- The sale remains finalized in POS.
- BC synchronization fails with an explicit mapping error.
- No fallback or incorrect salesperson is used.
- After correcting the mapping, **Save and sync now** or the scheduler retries the sale.

## Inventory synchronization

Inventory is calculated separately for every enabled branch mapping.

The BreMac AL API returns open Item Ledger Entries for the requested BC location. POS sums remaining quantities by item and writes the result to the corresponding POS location.

Items that no longer have an open quantity in BC are reset to zero in the mapped POS branch, preventing stale stock.

## Sale posting

Only finalized `sell` transactions are synchronized. Subscription invoices are excluded.

For each sale:

1. POS locates the mapping for the transaction's POS location.
2. POS resolves the BC customer number from its synchronization mapping.
3. POS resolves the transaction creator to the configured BC salesperson.
4. POS creates a BC sales invoice using the POS invoice number as the external document number and sends the salesperson code.
5. POS creates each invoice line using the item SKU/BC item number.
6. Each line receives the mapped BC `locationId`.
7. POS invokes the BC posting action.
8. BC creates the posted invoice and updates inventory for the mapped location.

An unmapped branch, user, customer, or item creates an explicit synchronization failure. POS does not silently use a default BC warehouse or salesperson.

## Synchronization frequency

The integration command runs every minute through Laravel's scheduler:

```bash
php artisan business-central:sync
```

The command synchronizes:

1. Items
2. Customers
3. Vendors
4. Salespeople
5. Inventory for every enabled branch mapping
6. Pending or retryable finalized sales

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
4. Synchronize a BC vendor and confirm it appears as a POS supplier.
5. Map the test POS user to a BC salesperson.
6. Finalize one sale in each POS branch.
7. Confirm each BC posted invoice has the expected salesperson and each line has the expected location.
8. Confirm BC inventory decreases only in that location.

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
- The POS customer came from BC and has a synchronized mapping.
- The POS user who created the sale is mapped to an active BC salesperson.
- Every sold SKU exists in BC.
- The integration account can create and post sales invoices.

Check `storage/logs/laravel.log` for the transaction ID and BC response.

## Technical deployment

See [the AL extension deployment guide](../integrations/business-central/README.md).
