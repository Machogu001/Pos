<?php

use App\Utils\BusinessUtil;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('business')) {
            return;
        }

        $businessUtil = app(BusinessUtil::class);

        DB::table('business')
            ->select('id', 'owner_id', 'enabled_modules')
            ->orderBy('id')
            ->chunkById(50, function ($businesses) use ($businessUtil) {
                foreach ($businesses as $business) {
                    $enabledModules = json_decode($business->enabled_modules ?: '[]', true);
                    $enabledModules = is_array($enabledModules) ? $enabledModules : [];

                    if (! in_array('account', $enabledModules, true)) {
                        continue;
                    }

                    $businessUtil->provisionDefaultAccountMappings((int) $business->id, ! empty($business->owner_id) ? (int) $business->owner_id : null);
                }
            });
    }

    public function down()
    {
        // No destructive rollback. Provisioned accounts/mappings may already be in use.
    }
};