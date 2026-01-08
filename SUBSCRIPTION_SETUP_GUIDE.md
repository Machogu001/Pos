# Subscription Feature - New Installation Setup

## Overview
This system includes a subscription enforcement feature that allows administrators to control whether users need an active subscription to use the system.

## Fresh Installation Steps

### 1. Run Migrations
```bash
php artisan migrate
```

This will create/update the `admin_settings` table with the `subscription_required` column (default: `false`).

### 2. Seed Database (Optional but Recommended)
```bash
php artisan db:seed --class=AdminSettingsSeeder
```

This creates the initial admin settings record with default values:
- `subscription_required`: `false` (users can access without subscription)
- `grace_period_days`: `7`
- `monthly_price`: `0`
- `quarterly_price`: `0`
- `yearly_price`: `0`
- `registration_price`: `0`
- `auto_renewal`: `false`

### 3. Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Default Behavior

### For New Installations:
- **Subscription enforcement is DISABLED by default**
- Users can access the system without any subscription
- Admins can enable subscription enforcement from the Admin Dashboard

### Enabling Subscription Enforcement:
1. Login as admin
2. Go to Admin Dashboard
3. Find "Subscription Enforcement" card
4. Click "Enable Subscription" button
5. Confirm the action

### Disabling Subscription Enforcement:
1. Go to Admin Dashboard
2. Click "Disable Subscription" button on the Subscription Enforcement card
3. Confirm the action

## How It Works

### When DISABLED (Default):
- All users can access the system
- No subscription checks are performed
- Business as usual

### When ENABLED:
- Middleware checks if user has active subscription before allowing access
- Users without active subscription are redirected to subscription plans page
- System admins are always exempt from subscription requirements
- Auth routes (login, logout, register) are always accessible
- Subscription-related routes are always accessible
- Admin routes are always accessible

## Admin Exemptions
The following user types are ALWAYS exempt from subscription requirements:
- Users with role `admin`
- Users with role `superadmin`
- Users with `is_superadmin = 1`
- Users with `user_type = 'admin'`
- Users where `isAdmin()` method returns true

## Database Schema

### admin_settings table:
```sql
subscription_required BOOLEAN DEFAULT FALSE
```

### Migration File:
`database/migrations/2026_01_08_152305_add_subscription_required_to_admin_settings_table.php`

### Model:
`App\AdminSetting`
- Fillable: `subscription_required`
- Casted to: `boolean`

### Middleware:
`App\Http\Middleware\CheckSubscription`
- Handles subscription enforcement logic
- Safely handles missing settings (fresh installs)

### Controller:
`App\Http\Controllers\AdminController@toggleSubscriptionRequirement`
- POST route: `/admin/settings/toggle-subscription-requirement`
- Returns JSON response with updated state

## Troubleshooting

### If settings table doesn't exist:
Run migrations: `php artisan migrate`

### If settings record doesn't exist:
The system will automatically create it when toggling subscription enforcement, or you can run:
```bash
php artisan db:seed --class=AdminSettingsSeeder
```

### If middleware throws errors:
The middleware is designed to safely handle:
- Missing `admin_settings` table (returns true, allows access)
- Missing settings record (returns true, allows access)
- Null values (defaults to false/disabled)

### Cache Issues:
Always clear caches after making changes:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

## UI Components

### Admin Dashboard Card:
- Shows current status (Enabled/Disabled)
- Visual indicator (green = enabled, gray = disabled)
- Toggle button with confirmation dialog
- Real-time updates without page refresh

### Confirmation Dialog:
- Toast-style overlay (not browser alert)
- Shows warning about impact
- Requires explicit confirmation
- Auto-closes on cancel or after success

## Security Considerations

1. **Authorization**: Only users with `admin` policy can toggle subscription enforcement
2. **CSRF Protection**: All forms include CSRF tokens
3. **Safe Defaults**: System defaults to disabled (more permissive) state
4. **Graceful Degradation**: If settings missing, allows access (fails open, not closed)

## Migration Rollback

To remove the subscription_required column:
```bash
php artisan migrate:rollback --step=1
```

Note: This will only rollback the most recent migration. Adjust step count as needed.

## Support

For issues or questions:
1. Check error logs: `storage/logs/laravel.log`
2. Verify migrations ran successfully
3. Ensure caches are cleared
4. Check middleware is registered in `app/Http/Kernel.php`
