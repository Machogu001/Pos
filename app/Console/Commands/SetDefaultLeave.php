<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;

class SetDefaultLeave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hrm:set-default-leave {--force : Overwrite existing values}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set default total_leave and remaining_leave for employees (defaults to config hrm.default_annual_leave)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $default = config('hrm.default_annual_leave', 21);
        $this->info("Using default leave days: {$default}");

        $query = Employee::query();
        if (! $this->option('force')) {
            $query = $query->where(function($q){
                $q->whereNull('total_leave')->orWhere('total_leave', 0)->orWhereNull('remaining_leave');
            });
        }

        $count = $query->count();
        if ($count === 0) {
            $this->info('No employees to update.');
            return 0;
        }

        $this->info("Updating {$count} employees...");
        $query->chunkById(100, function($employees) use ($default) {
            foreach ($employees as $e) {
                $e->total_leave = $e->total_leave ?: $default;
                $e->remaining_leave = $e->remaining_leave ?: $e->total_leave;
                $e->save();
            }
        });

        $this->info('Done.');
        return 0;
    }
}
