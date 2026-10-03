<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Business;
use App\BusinessLocation;
use App\CashRegister;
use App\Contact;
use App\Events\SellCreatedOrModified;
use App\Services\MobilePricingService;
use App\Services\MobileStockService;
use App\Transaction;
use App\Utils\CashRegisterUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use App\VariationLocationDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

class SaleController extends BaseMobileController
{
    public function __construct(
        protected ProductUtil $productUtil,
        protected CashRegisterUtil $cashRegisterUtil,
        protected Util $util,
        protected MobilePricingService $pricing
    ) {
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:final,draft,quotation'],
            'location_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $user = $request->user();
            if (! $user->can('sell.view') && ! $user->can('view_own_sell_only')) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            $locationId = isset($data['location_id']) ? (int) $data['location_id'] : null;
            if ($locationId && ! $this->canAccessLocation($user, $locationId)) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            $permitted = $this->permittedLocationIds($user);
            $query = Transaction::where('business_id', $user->business_id)
                ->where('type', 'sell')
                ->with(['contact', 'location', 'payment_lines'])
                ->when(! $user->can('sell.view') && $user->can('view_own_sell_only'), fn ($q) => $q->where('created_by', $user->id))
                ->when($permitted !== 'all', fn ($q) => $q->whereIn('location_id', $permitted))
                ->when($locationId, fn ($q) => $q->where('location_id', $locationId));

            if (! empty($data['status'])) {
                if ($data['status'] === 'quotation') {
                    $query->where('status', 'draft')->where('is_quotation', 1);
                } else {
                    $query->where('status', $data['status'])->when($data['status'] === 'draft', fn ($q) => $q->where(function ($sq) {
                        $sq->whereNull('is_quotation')->orWhere('is_quotation', 0);
                    }));
                }
            }

            if (! empty($data['q'])) {
                $term = '%'.$data['q'].'%';
                $query->where(function ($q) use ($term) {
                    $q->where('invoice_no', 'like', $term)
                        ->orWhereHas('contact', fn ($cq) => $cq->where('name', 'like', $term));
                });
            }

            $paginator = $query->latest('transaction_date')->paginate((int) ($data['per_page'] ?? 20));

            return $this->success($paginator->getCollection()->map(fn ($sale) => $this->saleSummaryPayload($sale))->values(), $this->paginationMeta($paginator));
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_sales_index']);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $transaction = $this->findAuthorizedSale($request, $id);
            if (! $transaction) {
                return $this->error('Sale not found.', 404, 'not_found');
            }

            return $this->success($this->saleWithReceipt($transaction));
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_sales_show', 'sale_id' => $id]);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer'],
            'contact_id' => ['nullable', 'integer'],
            'status' => ['required', 'in:final,draft,quotation'],
            'discount_type' => ['nullable', 'in:fixed,percentage'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variation_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price_inc_tax' => ['nullable', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string'],
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', 'string'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0'],
            'payments.*.note' => ['nullable', 'string'],
            'payments.*.card_number' => ['nullable', 'string'],
            'payments.*.transaction_no' => ['nullable', 'string'],
            'payments.*.checkout_request_id' => ['nullable', 'string'],
            'payments.*.mpesa_phone' => ['nullable', 'string'],
            'change_return' => ['nullable', 'numeric', 'min:0'],
            'client_reference' => ['required', 'string', 'max:191'],
        ]);

        $user = $request->user();
        $locationId = (int) $data['location_id'];
        $mapKey = $this->idempotencyKey($user->business_id, $user->id, $data['client_reference']);

        try {
            if (! $user->can('sell.create') && ! $user->can('direct_sell.access')) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }
            if (! $this->canAccessLocation($user, $locationId)) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }
            if (! CashRegister::where('user_id', $user->id)->where('status', 'open')->exists()) {
                return $this->error('No open cash register.', 409, 'register_closed');
            }

            if ($existingId = Cache::get($mapKey)) {
                $existing = $this->findAuthorizedSale($request, (int) $existingId);
                if ($existing) {
                    return $this->success($this->saleWithReceipt($existing), [], 200);
                }
            }

            $lockKey = $mapKey.':lock';
            $lock = null;
            $lockAcquired = false;
            try {
                $lock = Cache::lock($lockKey, 15);
                $lockAcquired = $lock->get();
            } catch (\Throwable $ignored) {
                $lockAcquired = Cache::add($lockKey, 1, now()->addSeconds(15));
            }

            if (! $lockAcquired) {
                return $this->error('Duplicate sale submission in progress.', 409, 'duplicate_request');
            }

            $transactionLevel = DB::transactionLevel();
            try {
                if ($existingId = Cache::get($mapKey)) {
                    $existing = $this->findAuthorizedSale($request, (int) $existingId);
                    if ($existing) {
                        return $this->success($this->saleWithReceipt($existing), [], 200);
                    }
                }

                DB::beginTransaction();
                // Serialize mobile check-and-save, and hold location stock until the sale commits.
                Business::whereKey($user->business_id)->lockForUpdate()->firstOrFail();
                VariationLocationDetails::where('location_id', $locationId)->orderBy('variation_id')
                    ->lockForUpdate()->get();
                $posInput = $this->buildPosInput($request, $data);
                if ($posInput instanceof \Illuminate\Http\JsonResponse) {
                    return $posInput;
                }

                $capturedTransaction = null;
                Event::listen(SellCreatedOrModified::class, function (SellCreatedOrModified $event) use (&$capturedTransaction, $user) {
                    if ($event->transaction->business_id == $user->business_id && $event->transaction->created_by == $user->id) {
                        $capturedTransaction = $event->transaction;
                    }
                });

                $startedAt = now()->subSecond();
                $posRequest = Request::create('/sell-pos', 'POST', $posInput);
                $posRequest->headers->set('Accept', 'application/json');
                $posRequest->setLaravelSession($request->session());
                $posRequest->setUserResolver(fn () => $user);

                $output = app(\App\Http\Controllers\SellPosController::class)->store($posRequest);
                if (! is_array($output)) {
                    return $this->error(
                        'Your subscription has expired or the invoice quota is used up. Please renew to continue selling.',
                        403,
                        'subscription_expired'
                    );
                }
                if ((int) ($output['success'] ?? 0) === 0) {
                    return $this->error($output['msg'] ?? 'Sale could not be saved.', 422);
                }

                $transaction = $capturedTransaction ?: Transaction::where('business_id', $user->business_id)
                    ->where('created_by', $user->id)
                    ->where('type', 'sell')
                    ->where('location_id', $locationId)
                    ->where('staff_note', $posInput['staff_note'] ?? null)
                    ->where('created_at', '>=', $startedAt)
                    ->latest('id')
                    ->first();

                if (! $transaction) {
                    return $this->error('Sale was saved but could not be located.', 500, 'server_error');
                }

                $transaction = $this->findAuthorizedSale($request, $transaction->id);
                DB::commit();
                Cache::put($mapKey, $transaction->id, now()->addDay());

                return $this->success($this->saleWithReceipt($transaction), [], 201);
            } finally {
                while (DB::transactionLevel() > $transactionLevel) {
                    DB::rollBack();
                }
                if ($lock) {
                    optional($lock)->release();
                } else {
                    Cache::forget($lockKey);
                }
            }
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_sales_store']);
        }
    }

    public function document(Request $request, int $id)
    {
        try {
            $transaction = $this->findAuthorizedSale($request, $id);
            if (! $transaction) {
                return $this->error('Sale not found.', 404, 'not_found');
            }
            $quotation = (bool) $transaction->is_quotation;
            $label = $quotation ? 'QUOTATION' : ($transaction->status === 'draft' ? 'DRAFT' : 'INVOICE');
            $filename = $label.'-'.preg_replace('/[^A-Za-z0-9._-]/', '_', $transaction->invoice_no).'.pdf';
            $pdf = app(TransactionUtil::class)->getEmailAttachmentForGivenTransaction(
                $transaction->business_id, $id, true, $label === 'DRAFT' ? 'DRAFT' : null
            );
            $pdf->SetTitle($filename);

            return $this->success([
                'filename' => $filename,
                'content_type' => 'application/pdf',
                'content_base64' => base64_encode($pdf->Output('', 'S')),
            ])->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_sale_document', 'sale_id' => $id]);
        }
    }

    public function validateStock(Request $request)
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variation_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);
        try {
            $user = $request->user();
            if ((! $user->can('sell.create') && ! $user->can('direct_sell.access'))
                || ! $this->canAccessLocation($user, (int) $data['location_id'])) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }
            BusinessLocation::where('business_id', $user->business_id)->findOrFail($data['location_id']);

            return $this->checkStock($user->business_id, (int) $data['location_id'], $data['items']);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_validate_stock']);
        }
    }

    protected function checkStock(int $businessId, int $locationId, array $items)
    {
        $demand = [];
        $availability = [];
        foreach ($items as $item) {
            $id = (int) $item['variation_id'];
            $variation = Variation::whereHas('product', fn ($query) => $query->where('business_id', $businessId))
                ->with('product')->find($id);
            if (! $variation) {
                return $this->error('Product not found.', 422, 'not_found');
            }
            $product = $variation->product;
            $quantity = (float) $item['quantity'];
            $stock = null;
            if ($product->type === 'combo') {
                $stock = app(MobileStockService::class)->comboAvailability($locationId, $variation->combo_variations);
                foreach ($this->productUtil->calculateComboDetails($locationId, $variation->combo_variations) as $component) {
                    if ($component['enable_stock']) {
                        $componentId = (int) $component['variation_id'];
                        $demand[$componentId] = ($demand[$componentId] ?? 0) + $quantity * $component['qty_required'];
                    }
                }
            } elseif ($product->enable_stock) {
                $stock = (float) VariationLocationDetails::where('variation_id', $id)
                    ->where('location_id', $locationId)->value('qty_available');
                $demand[$id] = ($demand[$id] ?? 0) + $quantity;
            }
            $availability[] = [
                'variation_id' => $id, 'enable_stock' => $stock !== null,
                'stock' => $stock,
            ];
        }
        foreach ($demand as $id => $required) {
            $stock = (float) VariationLocationDetails::where('variation_id', $id)
                ->where('location_id', $locationId)->value('qty_available');
            if ($this->money($required) > $this->money($stock)) {
                $variation = Variation::with('product.unit')->findOrFail($id);
                $name = $variation->product->name;

                return $this->error(
                    'Insufficient stock for '.$name.'. Requested: '.$this->money($required)
                        .'; available: '.$this->money($stock).'. Reduce the quantity before payment or completion.',
                    422, 'insufficient_stock', ['items' => ['Insufficient stock for '.$name.'.']]
                );
            }
        }

        return $this->success(['items' => $availability]);
    }

    protected function saleWithReceipt(Transaction $transaction): array
    {
        $text = $this->receiptText($transaction);
        try {
            $url = $this->util->getInvoiceUrl($transaction->id, $transaction->business_id);

            return $this->salePayload($transaction, $url, $text);
        } catch (\Throwable $exception) {
            // The sale is already saved: report the document failure without inviting a second payment.
            \Illuminate\Support\Facades\Log::error('Mobile sale receipt link failed', [
                'sale_id' => $transaction->id, 'exception' => $exception->getMessage(),
            ]);

            return $this->salePayload($transaction, null, $text) + [
                'receipt_error' => 'Sale saved, but its website receipt link could not be generated. Open or share the PDF, or contact the administrator.',
            ];
        }
    }

    protected function buildPosInput(Request $request, array $data)
    {
        $user = $request->user();
        $business = Business::findOrFail($user->business_id);
        $location = BusinessLocation::where('business_id', $user->business_id)->findOrFail($data['location_id']);
        $contactId = $data['contact_id'] ?? null;
        if (empty($contactId)) {
            $contactId = optional(Contact::where('business_id', $user->business_id)->where('is_default', 1)->first())->id;
        }
        $contact = Contact::where('business_id', $user->business_id)->whereIn('type', ['customer', 'both'])->find($contactId);
        if (! $contact) {
            return $this->error('Customer not found.', 422, null, ['contact_id' => ['Customer not found.']]);
        }

        $products = [];
        $total = 0.0;
        if ($data['status'] === 'final') {
            $stockCheck = $this->checkStock($user->business_id, $location->id, $data['items']);
            if ($stockCheck->getStatusCode() !== 200) {
                return $stockCheck;
            }
        }
        $editPrice = $user->can('edit_product_price_from_sale_screen') || $user->can('edit_product_price_from_pos_screen');

        foreach ($data['items'] as $index => $item) {
            $variationId = (int) $item['variation_id'];
            if (! Variation::where('id', $variationId)
                ->whereHas('product', fn ($q) => $q->where('business_id', $user->business_id))
                ->exists()) {
                return $this->error('Product not found.', 422, null, [
                    'items.'.$index.'.variation_id' => ['Product not found.'],
                ]);
            }

            $product = $this->productUtil->getDetailsFromVariation($variationId, $user->business_id, $location->id, false);
            $quantity = (float) $item['quantity'];

            $price = $this->pricing->priceLine($product, $business, $location, (int) $contact->id);
            if ($editPrice && isset($item['unit_price_inc_tax'])) {
                $price = $this->pricing->overridePrice($price, (float) $item['unit_price_inc_tax']);
            }

            $line = [
                'product_id' => $product->product_id,
                'variation_id' => $product->variation_id,
                'enable_stock' => $product->enable_stock,
                'product_type' => $product->product_type,
                'quantity' => $quantity,
                'unit_price' => $price['unit_price'],
                'unit_price_inc_tax' => $price['unit_price_inc_tax'],
                'item_tax' => $price['item_tax'],
                'tax_id' => $price['tax_id'],
                'line_discount_type' => $price['line_discount_type'],
                'line_discount_amount' => $price['line_discount_amount'],
                'discount_id' => $price['discount_id'],
                'product_unit_id' => $product->unit_id,
                'sub_unit_id' => null,
                'base_unit_multiplier' => 1,
                'sell_line_note' => $item['note'] ?? '',
            ];

            if ($product->product_type === 'combo' && ! empty($product->combo_products)) {
                $line['combo'] = collect($product->combo_products)->map(fn ($combo) => [
                    'product_id' => $combo['product_id'],
                    'variation_id' => $combo['variation_id'],
                    'quantity' => $combo['qty_required'] * $quantity,
                ])->values()->all();
            }

            $products[] = $line;
            $total += $quantity * $price['unit_price_inc_tax'];
        }

        $discountType = $data['discount_type'] ?? 'fixed';
        $discountAmount = (float) ($data['discount_amount'] ?? 0);
        $discountValue = $discountType === 'percentage' ? ($discountAmount / 100) * $total : $discountAmount;
        $finalTotal = max($total - $discountValue, 0);

        $payments = [];
        foreach (($data['payments'] ?? []) as $payment) {
            $payments[] = [
                'method' => $payment['method'],
                'amount' => (float) $payment['amount'],
                'note' => $payment['note'] ?? '',
                'card_number' => $payment['card_number'] ?? '',
                'card_transaction_number' => $payment['transaction_no'] ?? '',
                'transaction_no' => $payment['transaction_no'] ?? '',
                'checkout_request_id' => $payment['checkout_request_id'] ?? '',
                'mpesa_phone' => $payment['mpesa_phone'] ?? '',
            ];
        }
        $payments['change_return'] = [
            'method' => 'cash',
            'amount' => (float) ($data['change_return'] ?? 0),
            'note' => '',
            'is_return' => 1,
        ];

        return [
            'location_id' => (int) $data['location_id'],
            'contact_id' => $contact->id,
            'status' => $data['status'],
            'discount_type' => $discountType,
            'discount_amount' => $discountAmount,
            'tax_rate_id' => null,
            'sale_note' => $data['note'] ?? null,
            'additional_notes' => $data['note'] ?? null,
            'staff_note' => 'mobile_ref:'.$data['client_reference'],
            'is_suspend' => 0,
            'is_credit_sale' => 0,
            'invoice_layout_id' => $location->invoice_layout_id,
            'invoice_scheme_id' => $location->invoice_scheme_id,
            'default_price_group' => $location->selling_price_group_id,
            'price_group' => $this->pricing->priceGroupFor((int) $user->business_id, $location, (int) $contact->id) ?: 0,
            'shipping_charges' => 0,
            'rp_redeemed' => 0,
            'rp_redeemed_amount' => 0,
            'exchange_rate' => 1,
            'final_total' => $finalTotal,
            'products' => $products,
            'payment' => $payments,
            'change_return' => (float) ($data['change_return'] ?? 0),
            'is_created_from_api' => 1,
            'source' => 'mobile',
        ];
    }

    protected function findAuthorizedSale(Request $request, int $id): ?Transaction
    {
        $user = $request->user();
        $canViewAll = $user->can('sell.view');
        $canViewOwn = $user->can('view_own_sell_only') || $user->can('sell.create') || $user->can('direct_sell.access');
        if (! $canViewAll && ! $canViewOwn) {
            return null;
        }
        $permitted = $this->permittedLocationIds($user);

        return Transaction::where('business_id', $user->business_id)
            ->where('type', 'sell')
            ->where('id', $id)
            ->when(! $canViewAll, fn ($q) => $q->where('created_by', $user->id))
            ->when($permitted !== 'all', fn ($q) => $q->whereIn('location_id', $permitted))
            ->with(['contact', 'location', 'payment_lines', 'sell_lines.product.unit', 'sell_lines.variations.product_variation'])
            ->first();
    }

    protected function receiptText(Transaction $transaction): string
    {
        $lines = [];
        $lines[] = $this->center(optional($transaction->business)->name ?: config('app.name'));
        $lines[] = str_repeat('-', 32);
        $label = $transaction->is_quotation ? 'Quotation' : ($transaction->status === 'draft' ? 'Draft' : 'Invoice');
        $lines[] = $label.': '.$transaction->invoice_no;
        $lines[] = 'Date: '.optional($transaction->transaction_date ? \Carbon\Carbon::parse($transaction->transaction_date) : null)->format('Y-m-d H:i');
        $lines[] = 'Customer: '.substr(optional($transaction->contact)->name ?: '', 0, 22);
        $lines[] = str_repeat('-', 32);
        foreach ($transaction->sell_lines as $line) {
            $name = substr(optional($line->product)->name ?: 'Item', 0, 32);
            $lines[] = $name;
            $qty = $this->money($line->quantity).' x '.$this->money($line->unit_price_inc_tax);
            $total = $this->money($line->quantity * $line->unit_price_inc_tax);
            $lines[] = str_pad($qty, 20).str_pad((string) $total, 12, ' ', STR_PAD_LEFT);
        }
        $lines[] = str_repeat('-', 32);
        $lines[] = str_pad('TOTAL', 20).str_pad((string) $this->money($transaction->final_total), 12, ' ', STR_PAD_LEFT);
        $lines[] = str_pad('PAID', 20).str_pad((string) $this->money($transaction->payment_lines->where('is_return', 0)->sum('amount')), 12, ' ', STR_PAD_LEFT);
        foreach ($transaction->payment_lines->where('is_return', 0) as $payment) {
            $lines[] = strtoupper($payment->method).': '.$this->money($payment->amount);
            if ($payment->transaction_no) {
                $lines[] = 'Receipt: '.$payment->transaction_no;
            }
        }

        return implode("\n", array_map(fn ($line) => substr($line, 0, 32), $lines));
    }

    protected function center(string $text): string
    {
        $text = substr($text, 0, 32);
        $padding = max(0, (32 - strlen($text)) / 2);

        return str_repeat(' ', (int) floor($padding)).$text;
    }

    protected function idempotencyKey(int $businessId, int $userId, string $clientReference): string
    {
        return 'mobile_sale_idem:'.$businessId.':'.$userId.':'.sha1($clientReference);
    }
}
