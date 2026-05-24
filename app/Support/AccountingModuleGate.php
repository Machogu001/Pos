<?php

namespace App\Support;

use App\Business;

class AccountingModuleGate
{
    public static function isEnabledForBusiness(?int $businessId, $sessionEnabledModules = null): bool
    {
        $enabled = self::normalizeEnabledModules($sessionEnabledModules);

        if (self::containsAccountingModule($enabled)) {
            return true;
        }

        if (empty($businessId)) {
            return false;
        }

        $dbEnabled = Business::where('id', $businessId)->value('enabled_modules');

        return self::containsAccountingModule(self::normalizeEnabledModules($dbEnabled));
    }

    public static function normalizeEnabledModules($enabledModules): array
    {
        if (is_string($enabledModules)) {
            $decoded = json_decode($enabledModules, true);
            $enabledModules = is_array($decoded) ? $decoded : [];
        }

        return is_array($enabledModules) ? $enabledModules : [];
    }

    public static function containsAccountingModule(array $enabledModules): bool
    {
        return in_array('accounting_module', $enabledModules, true)
            || in_array('accounting', $enabledModules, true)
            || in_array('account', $enabledModules, true);
    }
}
