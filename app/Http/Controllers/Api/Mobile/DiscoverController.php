<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;

class DiscoverController extends BaseMobileController
{
    public function show(Request $request)
    {
        try {
            return $this->success([
                'server_url' => rtrim(url('/'), '/'),
            ]);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_discover']);
        }
    }
}
