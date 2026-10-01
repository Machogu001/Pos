<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Services\MobilePricingService;
use App\Utils\ProductUtil;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseMobileController
{
    protected ?Business $business = null;

    public function __construct(
        protected ProductUtil $productUtil,
        protected MobilePricingService $pricing
    ) {
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'location_id' => ['required', 'integer'],
            'contact_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $user = $request->user();
            if (! $this->canSearchProducts($user)) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }
            $locationId = (int) $data['location_id'];
            if (! $this->canAccessLocation($user, $locationId)) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            $location = BusinessLocation::where('business_id', $user->business_id)->findOrFail($locationId);
            $contactId = $this->resolveContactId($user->business_id, $data['contact_id'] ?? null);
            $paginator = $this->queryProducts($user->business_id, $locationId, $data['q'] ?? null)
                ->paginate((int) ($data['per_page'] ?? 20));

            $items = $paginator->getCollection()
                ->map(fn ($row) => $this->pricedPayload($row, $user->business_id, $location, $contactId))
                ->values();

            return $this->success($items, $this->paginationMeta($paginator));
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_products']);
        }
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'integer'],
            'contact_id' => ['nullable', 'integer'],
        ]);

        try {
            $user = $request->user();
            if (! $this->canSearchProducts($user)) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }
            $locationId = (int) $data['location_id'];
            if (! $this->canAccessLocation($user, $locationId)) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            $row = $this->queryProducts($user->business_id, $locationId, $data['code'], true)->first();
            if (! $row) {
                return $this->error('Product not found.', 404, 'not_found');
            }

            $location = BusinessLocation::where('business_id', $user->business_id)->findOrFail($locationId);
            $contactId = $this->resolveContactId($user->business_id, $data['contact_id'] ?? null);

            return $this->success($this->pricedPayload($row, $user->business_id, $location, $contactId));
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_product_lookup']);
        }
    }

    protected function canSearchProducts($user): bool
    {
        return $user->can('product.view') || $user->can('sell.create') || $user->can('direct_sell.access');
    }

    protected function queryProducts(int $businessId, int $locationId, ?string $term = null, bool $exact = false)
    {
        $query = Variation::query()
            ->join('products', 'variations.product_id', '=', 'products.id')
            ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('tax_rates as tax', 'products.tax', '=', 'tax.id')
            ->leftJoin('variation_location_details as vld', function ($join) use ($locationId) {
                $join->on('variations.id', '=', 'vld.variation_id')->where('vld.location_id', $locationId);
            })
            ->leftJoin('product_locations as pl', function ($join) use ($locationId) {
                $join->on('products.id', '=', 'pl.product_id')->where('pl.location_id', $locationId);
            })
            ->where('products.business_id', $businessId)
            ->where('products.not_for_selling', 0)
            ->where('products.type', '!=', 'modifier')
            ->whereNull('variations.deleted_at')
            ->whereNull('products.deleted_at')
            ->where(function ($query) use ($locationId) {
                $query->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))->from('product_locations')->whereColumn('product_locations.product_id', 'products.id');
                })->orWhereNotNull('pl.id');
            });

        if (! empty($term)) {
            $query->where(function ($query) use ($term, $exact) {
                $operator = $exact ? '=' : 'like';
                $value = $exact ? $term : '%'.$term.'%';
                $query->where('products.name', $operator, $value)
                    ->orWhere('products.sku', $operator, $value)
                    ->orWhere('variations.sub_sku', $operator, $value)
                    ->orWhere('variations.name', $operator, $value);
            });
        }

        return $query->select([
            'variations.id as variation_id',
            'products.id as product_id',
            DB::raw("IF(pv.is_dummy = 0, CONCAT(products.name, ' (', pv.name, ':', variations.name, ')'), products.name) as product_name"),
            'products.sku as product_sku',
            'variations.sub_sku',
            'products.type',
            'products.enable_stock',
            'vld.qty_available',
            'variations.default_sell_price',
            'variations.sell_price_inc_tax',
            'tax.amount as tax_rate',
            'units.short_name as unit',
            'products.image',
        ])->orderBy('products.name');
    }

    protected function productPayload($row, ?array $price = null): array
    {
        return [
            'variation_id' => (int) $row->variation_id,
            'product_id' => (int) $row->product_id,
            'name' => $row->product_name,
            'sku' => $row->sub_sku ?: $row->product_sku,
            'type' => $row->type,
            'unit' => $row->unit,
            'enable_stock' => (bool) $row->enable_stock,
            'stock' => $row->enable_stock ? $this->money($row->qty_available ?? 0) : null,
            'price_inc_tax' => $this->money($price['unit_price_inc_tax'] ?? $row->sell_price_inc_tax),
            'price_exc_tax' => $this->money(
                isset($price) ? $price['unit_price_inc_tax'] - $price['item_tax'] : $row->default_sell_price
            ),
            'tax_rate' => is_null($row->tax_rate) ? null : $this->money($row->tax_rate),
            'image_url' => ! empty($row->image) ? asset('/uploads/img/'.rawurlencode($row->image)) : asset('/img/default.png'),
        ];
    }

    /**
     * Applies the same price resolution used when the sale is saved, so the cart total
     * shown in the app matches the server-side invoice total.
     */
    protected function pricedPayload($row, int $businessId, BusinessLocation $location, ?int $contactId): array
    {
        $this->business ??= Business::findOrFail($businessId);
        $details = $this->productUtil->getDetailsFromVariation((int) $row->variation_id, $businessId, $location->id, false);
        $price = $this->pricing->priceLine($details, $this->business, $location, $contactId);

        return $this->productPayload($row, $price);
    }

    protected function resolveContactId(int $businessId, ?int $contactId): ?int
    {
        if (empty($contactId)) {
            return optional(Contact::where('business_id', $businessId)->where('is_default', 1)->first())->id;
        }

        return Contact::where('business_id', $businessId)->whereIn('type', ['customer', 'both'])->whereKey($contactId)->exists()
            ? $contactId
            : null;
    }
}
