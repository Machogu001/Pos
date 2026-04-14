<?php

use App\MpesaPayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mpesa_payments') || ! Schema::hasColumn('mpesa_payments', 'payment_type')) {
            return;
        }

        DB::table('mpesa_payments')
            ->orderBy('id')
            ->select([
                'id',
                'user_id',
                'business_id',
                'subscription_id',
                'consumed_by_transaction_id',
                'payment_type',
                'account_reference',
            ])
            ->chunkById(200, function ($payments) {
                foreach ($payments as $row) {
                    $payment = new MpesaPayment();
                    $payment->id = $row->id;
                    $payment->user_id = $row->user_id;
                    $payment->business_id = $row->business_id;
                    $payment->subscription_id = $row->subscription_id;
                    $payment->consumed_by_transaction_id = $row->consumed_by_transaction_id;
                    $payment->payment_type = $row->payment_type;
                    $payment->account_reference = $row->account_reference;

                    $normalizedType = MpesaPayment::normalizePaymentType($payment);

                    if ($normalizedType !== $row->payment_type) {
                        DB::table('mpesa_payments')
                            ->where('id', $row->id)
                            ->update(['payment_type' => $normalizedType]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }
};