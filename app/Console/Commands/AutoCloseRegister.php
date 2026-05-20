<?php

namespace App\Console\Commands;

use App\AdminSetting;
use App\CashRegister;
use App\CashRegisterTransaction;
use Illuminate\Console\Command;

class AutoCloseRegister extends Command
{
    protected $signature   = 'pos:autoCloseRegister';
    protected $description = 'Auto-close open cash registers at midnight when sales were made that day';

    public function handle(): int
    {
        $settings = AdminSetting::first();

        if (! $settings || ! $settings->auto_close_register) {
            $this->info('Auto close register is disabled. Skipping.');
            return 0;
        }

        /** @var \Illuminate\Database\Eloquent\Collection $openRegisters */
        $openRegisters = CashRegister::where('status', 'open')->get();

        if ($openRegisters->isEmpty()) {
            $this->info('No open registers found. Nothing to do.');
            return 0;
        }

        $closed  = 0;
        $skipped = 0;

        foreach ($openRegisters as $register) {
            // Close if the register has at least one sell transaction since it was opened.
            // Using register->created_at (open time) avoids the midnight date-rollover bug
            // where Carbon::today() is already the next calendar day when this runs at 00:00.
            $hasSales = CashRegisterTransaction::where('cash_register_id', $register->id)
                ->where('transaction_type', 'sell')
                ->where('created_at', '>=', $register->created_at)
                ->exists();

            if (! $hasSales) {
                \Log::info("pos:autoCloseRegister — register #{$register->id} skipped (no sales since open, user {$register->user_id})");
                $this->line("  Register #{$register->id} (user {$register->user_id}): no sales since open — skipped.");
                $skipped++;
                continue;
            }

            $register->update([
                'status'       => 'close',
                'closed_at'    => now()->format('Y-m-d H:i:s'),
                'closing_note' => 'Auto-closed by system at midnight.',
            ]);

            \Log::info("pos:autoCloseRegister — register #{$register->id} closed (user {$register->user_id}, business {$register->business_id})");
            $this->info("  Register #{$register->id} (user {$register->user_id}): closed ✓");
            $closed++;
        }

        $this->info("Done. Closed: {$closed} | Skipped (no sales): {$skipped}");
        return 0;
    }
}
