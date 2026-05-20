<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddJournalEntryIdToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('transactions') || Schema::hasColumn('transactions', 'journal_entry_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->bigInteger('journal_entry_id')->unsigned()->nullable()->after('location_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'journal_entry_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('journal_entry_id');
        });
    }
}
