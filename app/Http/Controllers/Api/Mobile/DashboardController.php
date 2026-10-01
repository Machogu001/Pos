<?php

namespace App\Http\Controllers\Api\Mobile;

use App\BusinessLocation;
use App\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseMobileController
{
    public function show(Request $request)
    {
        $data = $request->validate([
            'location_id' => ['nullable', 'integer'],
            'period' => ['nullable', 'in:today,week,month'],
        ]);

        try {
            $user = $request->user();
            if (! $user->can('dashboard.data')) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            $locationId = isset($data['location_id']) ? (int) $data['location_id'] : null;
            if ($locationId && ! $this->canAccessLocation($user, $locationId)) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            $period = $data['period'] ?? 'today';
            [$from, $to] = match ($period) {
                'week' => [now()->startOfWeek(), now()->endOfWeek()],
                'month' => [now()->startOfMonth(), now()->endOfMonth()],
                default => [now()->startOfDay(), now()->endOfDay()],
            };

            $permitted = $this->permittedLocationIds($user);
            $sales = Transaction::where('business_id', $user->business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereBetween('transaction_date', [$from, $to])
                ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
                ->when($permitted !== 'all', fn ($query) => $query->whereIn('location_id', $permitted));

            $totals = (clone $sales)->select(
                DB::raw('COALESCE(SUM(final_total), 0) as total_sales'),
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM((SELECT COALESCE(SUM(IF(tp.is_return = 1, -1 * tp.amount, tp.amount)), 0) FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id)), 0) as total_paid')
            )->first();

            $totalExpense = (float) Transaction::where('business_id', $user->business_id)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$from, $to])
                ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
                ->when($permitted !== 'all', fn ($query) => $query->whereIn('location_id', $permitted))
                ->sum('final_total');

            $recent = (clone $sales)->with(['contact', 'location', 'payment_lines'])->latest('transaction_date')->limit(5)->get()
                ->map(fn ($sale) => $this->saleSummaryPayload($sale));

            $totalSales = (float) $totals->total_sales;
            $totalPaid = (float) $totals->total_paid;

            return $this->success([
                'period' => $period,
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
                'total_sales' => $this->money($totalSales),
                'sales_count' => (int) $totals->sales_count,
                'total_paid' => $this->money($totalPaid),
                'total_due' => $this->money($totalSales - $totalPaid),
                'total_expense' => $this->money($totalExpense),
                'net' => $this->money($totalSales - $totalExpense),
                'recent_sales' => $recent,
            ]);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_dashboard']);
        }
    }
}
