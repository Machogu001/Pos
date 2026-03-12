# Dual M-Pesa Credentials Feature

## Overview
This feature allows the system to use different M-Pesa credentials for subscription payments versus regular POS/sell payments.

### Use Case
- **Subscription Payments**: Use admin-configured credentials (stored in database)
- **POS/Sell Payments**: Use default credentials (stored in `.env` file)

## Implementation Details

### Database Changes
**Migration**: `2026_01_09_000000_add_subscription_mpesa_credentials_to_admin_settings.php`

Added the following columns to `admin_settings` table:
- `subscription_mpesa_consumer_key` (nullable)
- `subscription_mpesa_consumer_secret` (nullable)
- `subscription_mpesa_shortcode` (nullable)
- `subscription_mpesa_passkey` (nullable)
- `subscription_mpesa_callback` (nullable)

### Code Changes

#### 1. AdminSetting Model (`app/AdminSetting.php`)
Added the 5 new fields to the `$fillable` array to allow mass assignment.

#### 2. MpesaController (`app/Http/Controllers/MpesaController.php`)
Added `setCustomCredentials()` method that allows runtime switching of M-Pesa credentials:

```php
public function setCustomCredentials($consumerKey, $consumerSecret, $shortCode, $passkey, $callbackUrl)
{
    $this->consumerKey = $consumerKey;
    $this->consumerSecret = $consumerSecret;
    $this->shortCode = $shortCode;
    $this->passkey = $passkey;
    $this->callbackUrl = $callbackUrl;
}
```

#### 3. SubscriptionController (`app/Http/Controllers/SubscriptionController.php`)
Updated all subscription payment initiation points (3 locations: lines ~313, ~577, ~814) to check for and use subscription-specific credentials:

```php
$mpesaController = new MpesaController();

// Use subscription-specific credentials if available
$settings = AdminSetting::first();
if ($settings && $settings->subscription_mpesa_consumer_key) {
    $mpesaController->setCustomCredentials(
        $settings->subscription_mpesa_consumer_key,
        $settings->subscription_mpesa_consumer_secret,
        $settings->subscription_mpesa_shortcode,
        $settings->subscription_mpesa_passkey,
        $settings->subscription_mpesa_callback
    );
}

$response = $mpesaController->initiatePaymentDirect(...);
```

#### 4. Admin Dashboard UI (`resources/views/admin/dashboard.blade.php`)
Added a new card section after the "Subscription Enforcement Toggle" with:
- 5 input fields for M-Pesa credentials
- Password visibility toggle button
- AJAX form submission with SweetAlert feedback
- Validation requiring all 5 fields if any are filled

#### 5. AdminController (`app/Http/Controllers/AdminController.php`)
Added `updateSubscriptionMpesaCredentials()` method that:
- Validates the input fields
- Enforces "all or nothing" rule (all 5 fields must be filled if configuring)
- Returns JSON response for AJAX handling

#### 6. Routes (`routes/web.php`)
Added route: `admin.settings.update-subscription-mpesa`

## Configuration

### Admin Dashboard
1. Navigate to the Admin Dashboard
2. Locate the "Subscription M-Pesa Credentials" card
3. Fill in all 5 M-Pesa credential fields:
   - Consumer Key
   - Consumer Secret
   - Shortcode
   - Passkey
   - Callback URL
4. Click "Save M-Pesa Credentials"

### Behavior
- **If credentials are configured**: All subscription payments will use the admin-configured credentials
- **If credentials are NOT configured**: All subscription payments will fall back to the `.env` credentials (backward compatible)
- **POS/Sell payments**: Always use `.env` credentials regardless of subscription settings

## Testing

### Test Subscription Payment
1. Configure subscription M-Pesa credentials in admin dashboard
2. Register a new user or renew an existing subscription
3. Initiate M-Pesa payment
4. Verify the payment uses the configured shortcode (check M-Pesa logs or callback)

### Test POS Payment
1. Make a sale through POS
2. Pay with M-Pesa
3. Verify the payment uses the `.env` shortcode (should be different from subscription shortcode)

### Test Fallback
1. Clear all subscription M-Pesa credentials in admin dashboard
2. Initiate a subscription payment
3. Verify the payment uses the `.env` credentials

## Security Notes
- Credentials are stored in the database (ensure proper backup procedures)
- Password fields use input type="password" with toggle visibility
- All credential fields are nullable to maintain backward compatibility
- The system requires admin authorization to update credentials

## Backward Compatibility
- Existing installations without subscription credentials configured will continue to work
- All payments will use `.env` credentials until admin configures subscription-specific ones
- No migration is required for existing data

## Migration Command
```bash
php artisan migrate
```

## Files Modified
1. `database/migrations/2026_01_09_000000_add_subscription_mpesa_credentials_to_admin_settings.php` (NEW)
2. `app/AdminSetting.php`
3. `app/Http/Controllers/MpesaController.php`
4. `app/Http/Controllers/SubscriptionController.php`
5. `app/Http/Controllers/AdminController.php`
6. `resources/views/admin/dashboard.blade.php`
7. `routes/web.php`

## Author
Implemented on January 9, 2026
