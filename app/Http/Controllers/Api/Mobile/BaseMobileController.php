<?php

namespace App\Http\Controllers\Api\Mobile;

use App\BusinessLocation;
use App\CashRegister;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Transaction;
use App\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class BaseMobileController extends Controller
{
    protected function success($data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];
        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function error(string $message, int $status, ?string $code = null, array $errors = []): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];
        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }
        if (! empty($code)) {
            $payload['code'] = $code;
        }

        return response()->json($payload, $status);
    }

    protected function serverError(\Throwable $exception, array $context = []): JsonResponse
    {
        Log::error('Mobile API error: '.$exception->getMessage(), $context + [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        return $this->error('Something went wrong. Please try again.', 500, 'server_error');
    }

    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    protected function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => trim(($user->surname ? $user->surname.' ' : '').$user->first_name.' '.$user->last_name),
            'email' => $user->email,
        ];
    }

    protected function businessPayload($business): array
    {
        $currency = $business?->currency;

        return [
            'id' => $business?->id,
            'name' => $business?->name,
            'currency' => [
                'code' => $currency?->code,
                'symbol' => $currency?->symbol,
                'thousand_separator' => $currency?->thousand_separator,
                'decimal_separator' => $currency?->decimal_separator,
                'precision' => (int) (config('constants.currency_precision', 2)),
            ],
            'logo_url' => ! empty($business?->logo) ? asset('uploads/business_logos/'.$business->logo) : null,
        ];
    }

    protected function registerPayload(?CashRegister $register): ?array
    {
        if (! $register) {
            return null;
        }

        $openingAmount = optional($register->cash_register_transactions()->where('transaction_type', 'initial')->first())->amount;

        return [
            'id' => $register->id,
            'location_id' => $register->location_id,
            'status' => $register->status,
            'opened_at' => optional($register->created_at)->toIso8601String(),
            'opening_amount' => $this->money($openingAmount ?? 0),
        ];
    }

    protected function locationPayload(BusinessLocation $location, array $paymentMethods = []): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'location_id' => $location->location_id,
            'default_payment_methods' => $paymentMethods,
        ];
    }

    protected function customerPayload(Contact $contact): array
    {
        $balanceDue = (float) (($contact->total_invoice ?? 0) - ($contact->invoice_received ?? 0) + ($contact->opening_balance ?? 0) - ($contact->opening_balance_paid ?? 0));

        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'mobile' => $contact->mobile,
            'email' => $contact->email,
            'contact_id' => $contact->contact_id,
            'balance_due' => $this->money($balanceDue),
            'is_default' => (bool) ($contact->is_default ?? false),
        ];
    }

    protected function saleSummaryPayload(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'invoice_no' => $transaction->invoice_no,
            'transaction_date' => optional($transaction->transaction_date ? \Carbon\Carbon::parse($transaction->transaction_date) : null)->toIso8601String(),
            'customer_name' => optional($transaction->contact)->name,
            'final_total' => $this->money($transaction->final_total),
            'total_paid' => $this->money($transaction->total_paid ?? $transaction->payment_lines->where('is_return', 0)->sum('amount')),
            'payment_status' => $transaction->payment_status,
            'status' => $transaction->is_quotation ? 'quotation' : $transaction->status,
            'location_name' => optional($transaction->location)->name,
        ];
    }

    protected function salePayload(Transaction $transaction, ?string $receiptUrl = null, ?string $receiptText = null): array
    {
        $summary = $this->saleSummaryPayload($transaction);
        $items = $transaction->sell_lines->map(function ($line) {
            $name = optional($line->product)->name;
            if (optional($line->variations)->name && optional(optional($line->variations)->product_variation)->is_dummy == 0) {
                $name .= ' - '.optional(optional($line->variations)->product_variation)->name.' - '.$line->variations->name;
            }

            return [
                'name' => $name,
                'sku' => optional($line->variations)->sub_sku,
                'quantity' => $this->money($line->quantity),
                'unit' => optional(optional($line->product)->unit)->short_name,
                'unit_price_inc_tax' => $this->money($line->unit_price_inc_tax),
                'line_total' => $this->money($line->quantity * $line->unit_price_inc_tax),
            ];
        })->values()->all();

        $payments = $transaction->payment_lines->map(fn ($payment) => [
            'method' => $payment->method,
            'amount' => $this->money($payment->amount),
            'paid_on' => optional($payment->paid_on ? \Carbon\Carbon::parse($payment->paid_on) : null)->toIso8601String(),
            'reference' => $payment->payment_ref_no ?: ($payment->transaction_no ?: null),
        ])->values()->all();

        return $summary + [
            'items' => $items,
            'subtotal' => $this->money($transaction->total_before_tax),
            'discount_amount' => $this->money($transaction->discount_amount),
            'tax_amount' => $this->money($transaction->tax_amount),
            'payments' => $payments,
            'change_return' => $this->money($transaction->payment_lines->where('is_return', 1)->sum('amount')),
            'receipt_url' => $receiptUrl,
            'receipt_text' => $receiptText,
        ];
    }

    protected function money($value): float
    {
        return round((float) $value, 4);
    }

    protected function permittedLocationIds(User $user)
    {
        return $user->permitted_locations($user->business_id);
    }

    protected function canAccessLocation(User $user, int $locationId): bool
    {
        $permitted = $this->permittedLocationIds($user);

        return $permitted === 'all' || in_array($locationId, $permitted, true) || in_array((string) $locationId, $permitted, true);
    }
}
