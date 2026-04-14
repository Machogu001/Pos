<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('business') || ! Schema::hasColumn('business', 'common_settings')) {
            return;
        }

        $defaultMappings = config('constants.default_account_mappings', []);

        DB::table('business')
            ->select('id', 'common_settings')
            ->orderBy('id')
            ->chunkById(100, function ($businesses) use ($defaultMappings) {
                foreach ($businesses as $business) {
                    $commonSettings = json_decode($business->common_settings ?: '[]', true);
                    $commonSettings = is_array($commonSettings) ? $commonSettings : [];

                    $typeMappings = ! empty($commonSettings['default_account_mappings']) && is_array($commonSettings['default_account_mappings'])
                        ? $commonSettings['default_account_mappings']
                        : [];

                    foreach ($defaultMappings as $mappingKey => $defaultAccountId) {
                        if (empty($typeMappings[$mappingKey]) && ! empty($defaultAccountId)) {
                            $typeMappings[$mappingKey] = (int) $defaultAccountId;
                        }
                    }

                    if (empty($typeMappings['purchase_tax']) && ! empty($typeMappings['tax'])) {
                        $typeMappings['purchase_tax'] = $typeMappings['tax'];
                    }

                    if (empty($typeMappings['sales_tax']) && ! empty($typeMappings['tax'])) {
                        $typeMappings['sales_tax'] = $typeMappings['tax'];
                    }

                    if (empty($typeMappings)) {
                        continue;
                    }

                    $commonSettings['default_account_mappings'] = $typeMappings;

                    DB::table('business')
                        ->where('id', $business->id)
                        ->update([
                            'common_settings' => json_encode($commonSettings),
                        ]);
                }
            });
    }

    public function down()
    {
        if (! Schema::hasTable('business') || ! Schema::hasColumn('business', 'common_settings')) {
            return;
        }

        DB::table('business')
            ->select('id', 'common_settings')
            ->orderBy('id')
            ->chunkById(100, function ($businesses) {
                foreach ($businesses as $business) {
                    $commonSettings = json_decode($business->common_settings ?: '[]', true);
                    $commonSettings = is_array($commonSettings) ? $commonSettings : [];

                    $typeMappings = ! empty($commonSettings['default_account_mappings']) && is_array($commonSettings['default_account_mappings'])
                        ? $commonSettings['default_account_mappings']
                        : [];

                    $legacyTax = $typeMappings['tax'] ?? null;

                    if (($typeMappings['purchase_tax'] ?? null) === $legacyTax) {
                        unset($typeMappings['purchase_tax']);
                    }

                    if (($typeMappings['sales_tax'] ?? null) === $legacyTax) {
                        unset($typeMappings['sales_tax']);
                    }

                    if (empty($typeMappings)) {
                        unset($commonSettings['default_account_mappings']);
                    } else {
                        $commonSettings['default_account_mappings'] = $typeMappings;
                    }

                    DB::table('business')
                        ->where('id', $business->id)
                        ->update([
                            'common_settings' => empty($commonSettings) ? null : json_encode($commonSettings),
                        ]);
                }
            });
    }
};