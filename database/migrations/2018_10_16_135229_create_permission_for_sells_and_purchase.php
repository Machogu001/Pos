<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissions = [
            ['name' => 'purchase.payments', 'guard_name' => 'web'],
            ['name' => 'sell.payments', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate($perm);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::whereIn('name', ['purchase.payments', 'sell.payments'])
            ->where('guard_name', 'web')
            ->delete();
    }
};
