<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Utils\Util;
use Illuminate\Http\Request;

class PaymentMethodController extends BaseMobileController
{
    public function __construct(protected Util $util)
    {
    }

    public function index(Request $request)
    {
        $data = $request->validate(['location_id' => ['required', 'integer']]);

        try {
            $user = $request->user();
            $locationId = (int) $data['location_id'];
            if (! $this->canAccessLocation($user, $locationId)) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            $methods = $this->util->payment_types($locationId, true, $user->business_id);

            return $this->success(collect($methods)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])->values());
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_payment_methods']);
        }
    }
}
