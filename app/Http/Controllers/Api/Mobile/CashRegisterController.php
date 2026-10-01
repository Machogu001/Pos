<?php

namespace App\Http\Controllers\Api\Mobile;

use App\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends BaseMobileController
{
    public function show(Request $request)
    {
        try {
            $register = CashRegister::where('user_id', $request->user()->id)->where('status', 'open')->latest()->first();

            return $this->success($this->registerPayload($register));
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_register_show']);
        }
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer'],
            'opening_amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $user = $request->user();
            if (! $this->canAccessLocation($user, (int) $data['location_id'])) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }
            if (CashRegister::where('user_id', $user->id)->where('status', 'open')->exists()) {
                return $this->error('Cash register already open.', 409, 'register_already_open');
            }

            DB::beginTransaction();
            $register = CashRegister::create([
                'business_id' => $user->business_id,
                'user_id' => $user->id,
                'status' => 'open',
                'location_id' => $data['location_id'],
                'created_at' => now()->format('Y-m-d H:i:00'),
            ]);
            if ((float) $data['opening_amount'] > 0) {
                $register->cash_register_transactions()->create([
                    'amount' => $data['opening_amount'],
                    'pay_method' => 'cash',
                    'type' => 'credit',
                    'transaction_type' => 'initial',
                ]);
            }
            DB::commit();

            return $this->success($this->registerPayload($register->fresh()), [], 201);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return $this->serverError($exception, ['action' => 'mobile_register_open']);
        }
    }

    public function close(Request $request)
    {
        $data = $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0'],
            'closing_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $user = $request->user();
            $register = CashRegister::where('user_id', $user->id)->where('status', 'open')->latest()->first();
            if (! $register) {
                return $this->error('No open cash register.', 409, 'register_closed');
            }

            $register->update([
                'closing_amount' => $data['closing_amount'],
                'closing_note' => $data['closing_note'] ?? null,
                'closed_at' => now()->format('Y-m-d H:i:s'),
                'status' => 'close',
            ]);

            return $this->success([
                'id' => $register->id,
                'status' => 'close',
                'closing_amount' => $this->money($register->closing_amount),
            ]);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_register_close']);
        }
    }
}
