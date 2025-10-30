<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class BackfillActivatedAtForSubscriptions extends Migration
{
    /**
     * Run the migrations.
     * Backfill activated_at for subscriptions that are active but have null activated_at.
     * We'll set activated_at = start_date if available, otherwise updated_at.
     *
     * @return void
     */
    public function up()
    {
        // Set activated_at to start_date where available
        DB::table('subscriptions')
            ->whereNull('activated_at')
            ->where('status', 'active')
            ->whereNotNull('start_date')
            ->update(['activated_at' => DB::raw('start_date')]);

        // For any remaining active subscriptions with null activated_at, set to updated_at or created_at
        DB::table('subscriptions')
            ->whereNull('activated_at')
            ->where('status', 'active')
            ->update(['activated_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    /**
     * Reverse the migrations.
     * We will not revert the backfill to avoid data loss.
     *
     * @return void
     */
    public function down()
    {
        // Intentionally left blank.
    }
}
