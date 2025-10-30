<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddPaymentTypeAndUniqueCheckoutRequestIdToMpesaPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a `payment_type` column and (if safe) a unique index on `checkout_request_id`.
     * The unique index will only be created if there are no duplicate checkout_request_id values
     * in the current table to avoid migration failures in production. If duplicates exist,
     * the index creation will be skipped and a warning logged so the duplicates can be cleaned up
     * manually before re-running or creating a targeted fix.
     */
    public function up()
    {
        Schema::table('mpesa_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('mpesa_payments', 'payment_type')) {
                $table->string('payment_type')->nullable()->default('other')->after('subscription_id');
            }
        });

        // Check for duplicate entries for the composite (checkout_request_id, payment_type)
        $duplicates = DB::select("SELECT checkout_request_id, payment_type, COUNT(*) as c FROM mpesa_payments WHERE checkout_request_id IS NOT NULL GROUP BY checkout_request_id, payment_type HAVING c > 1");

        if (empty($duplicates)) {
            // Safe to add composite unique index
            Schema::table('mpesa_payments', function (Blueprint $table) {
                try {
                    $table->unique(['checkout_request_id', 'payment_type'], 'mpesa_payments_checkout_request_id_payment_type_unique');
                } catch (\Exception $e) {
                    Log::warning('Could not create composite unique index on (checkout_request_id, payment_type): ' . $e->getMessage());
                }
            });
        } else {
            // Log a warning so the operator can inspect and fix duplicates before enforcing uniqueness
            Log::warning('Skipped creating composite unique index mpesa_payments.(checkout_request_id,payment_type) because duplicates exist. Duplicate rows: ' . count($duplicates));
        }
    }

    /**
     * Reverse the migrations.
     *
     * Drops the payment_type column and index if present.
     */
    public function down()
    {
        Schema::table('mpesa_payments', function (Blueprint $table) {
            // Drop unique index if exists
            try {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = array_map(function($i){ return $i->getName(); }, $sm->listTableIndexes('mpesa_payments'));
                if (in_array('mpesa_payments_checkout_request_id_unique', $indexes)) {
                    $table->dropUnique('mpesa_payments_checkout_request_id_unique');
                }
            } catch (\Exception $e) {
                // best-effort
            }

            if (Schema::hasColumn('mpesa_payments', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
        });
    }
}
