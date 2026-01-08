# Pos
Pos System

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
- Create/update database tables
- Initialize admin settings with default values
- Clear all caches for a fresh start
