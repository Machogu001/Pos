<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes P0: leaves table was missing attachment, half_day, company_id, department_id columns.
 * The LeaveController writes all of these; without the columns the writes silently failed
 * or threw errors in strict mode.
 */
class AddMissingColumnsToHrmLeaves extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leaves')) {
            return;
        }

        Schema::table('leaves', function (Blueprint $table) {
            if (! Schema::hasColumn('leaves', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('employee_id');
            }
            if (! Schema::hasColumn('leaves', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('company_id');
            }
            if (! Schema::hasColumn('leaves', 'business_id')) {
                $table->unsignedBigInteger('business_id')->nullable()->after('department_id');
            }
            if (! Schema::hasColumn('leaves', 'attachment')) {
                // Stores path relative to storage/app/private/hrm/leaves/
                $table->string('attachment')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('leaves', 'half_day')) {
                $table->boolean('half_day')->default(false)->after('attachment');
            }
            if (! Schema::hasColumn('leaves', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        try {
            Schema::table('leaves', function (Blueprint $table) {
                $table->index('business_id', 'leaves_business_id_idx');
                $table->index(['employee_id', 'status'], 'leaves_employee_status_idx');
            });
        } catch (\Throwable $e) {
            // Index already exists
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('leaves')) {
            return;
        }

        Schema::table('leaves', function (Blueprint $table) {
            $columns = ['company_id', 'department_id', 'business_id', 'attachment', 'half_day', 'approved_at'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('leaves', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
