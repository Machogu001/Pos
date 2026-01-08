<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Skip if table already exists (for safety)
        if (Schema::hasTable('payment_accounts')) {
            return;
        }

        // Create the table with all columns
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->string('type', 50); // e.g., 'bank', 'cash', 'credit_card', 'mobile_money', 'digital_wallet'
            $table->string('account_number', 100)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->softDeletes();
            $table->timestamps();

            // Add indexes
            $table->index('business_id');
            $table->index('type');
        });

        // Add foreign key constraint using raw SQL
        DB::statement('
            ALTER TABLE payment_accounts
            ADD CONSTRAINT payment_accounts_business_id_foreign
            FOREIGN KEY (business_id)
            REFERENCES business(id)
            ON DELETE CASCADE
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // First remove foreign key if it exists
        DB::statement('
            ALTER TABLE payment_accounts
            DROP FOREIGN KEY IF EXISTS payment_accounts_business_id_foreign
        ');

        // Then drop table
        Schema::dropIfExists('payment_accounts');
    }
};