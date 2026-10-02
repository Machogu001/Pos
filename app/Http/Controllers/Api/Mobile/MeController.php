<?php

namespace App\Http\Controllers\Api\Mobile;

use App\BusinessLocation;
use App\CashRegister;
use App\Utils\Util;
use Illuminate\Http\Request;

class MeController extends BaseMobileController
{
    public function __construct(protected Util $util)
    {
    }

    public function show(Request $request)
    {
        try {
            $user = $request->user();
            $business = $user->business()->with('currency')->first();
            $permitted = $this->permittedLocationIds($user);
            $locations = BusinessLocation::where('business_id', $user->business_id)->Active()
                ->when($permitted !== 'all', fn ($query) => $query->whereIn('id', $permitted))
                ->get()
                ->map(function (BusinessLocation $location) {
                    return $this->locationPayload($location, $this->paymentMethods($location->id));
                })
                ->values();

            $register = CashRegister::where('user_id', $user->id)->where('status', 'open')->latest()->first();
            $canSell = $user->can('sell.create') || $user->can('direct_sell.access');

            return $this->success([
                'user' => $this->userPayload($user),
                'business' => $this->businessPayload($business),
                'locations' => $locations,
                'permissions' => [
                    'is_admin' => $this->util->is_admin($user),
                    'sell_create' => $canSell,
                    'view_sales' => $user->can('sell.view') || $user->can('view_own_sell_only'),
                    'view_products' => $user->can('product.view'),
                    'view_customers' => $user->can('customer.view') || $user->can('customer.view_own'),
                    'create_customer' => $user->can('customer.create'),
                    'view_dashboard' => $user->can('dashboard.data'),
                    'close_register' => $user->can('close_cash_register'),
                    'edit_price' => $user->can('edit_product_price_from_sale_screen')
                        || $user->can('edit_product_price_from_pos_screen'),
                    'discount' => $user->can('edit_product_discount_from_sale_screen'),
                ],
                'register' => $this->registerPayload($register),
            ]);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_me']);
        }
    }

    protected function paymentMethods(int $locationId): array
    {
        $methods = $this->util->payment_types($locationId, true, auth()->user()->business_id);

        return collect($methods)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all();
    }
}
