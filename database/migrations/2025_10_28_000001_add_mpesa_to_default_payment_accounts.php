<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\BusinessLocation;

class AddMpesaToDefaultPaymentAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add 'mpesa' => ['is_enabled' => '1', 'account' => null] to default_payment_accounts JSON for all locations
        if (class_exists(BusinessLocation::class)) {
            $locations = BusinessLocation::all();
            foreach ($locations as $location) {
                $dpa = ! empty($location->default_payment_accounts) ? json_decode($location->default_payment_accounts, true) : [];
                if (! isset($dpa['mpesa'])) {
                    $dpa['mpesa'] = ['is_enabled' => '1', 'account' => null];
                    $location->default_payment_accounts = json_encode($dpa);
                    $location->save();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (class_exists(BusinessLocation::class)) {
            $locations = BusinessLocation::all();
            foreach ($locations as $location) {
                $dpa = ! empty($location->default_payment_accounts) ? json_decode($location->default_payment_accounts, true) : [];
                if (isset($dpa['mpesa'])) {
                    unset($dpa['mpesa']);
                    $location->default_payment_accounts = json_encode($dpa);
                    $location->save();
                }
            }
        }
    }
}
