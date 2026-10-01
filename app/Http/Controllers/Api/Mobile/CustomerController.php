<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Contact;
use App\Events\ContactCreatedOrModified;
use App\Utils\ContactUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends BaseMobileController
{
    public function __construct(protected ContactUtil $contactUtil)
    {
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $user = $request->user();
            if (! $user->can('customer.view') && ! $user->can('customer.view_own')) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            $query = Contact::query()->onlyCustomers()->active()
                ->where('contacts.business_id', $user->business_id)
                ->leftJoin('transactions as t', 'contacts.id', '=', 't.contact_id')
                ->groupBy('contacts.id')
                ->select(
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                    'contacts.*'
                );

            if (! empty($data['q'])) {
                $q = '%'.$data['q'].'%';
                $query->where(function ($query) use ($q) {
                    $query->where('contacts.name', 'like', $q)
                        ->orWhere('contacts.mobile', 'like', $q)
                        ->orWhere('contacts.email', 'like', $q)
                        ->orWhere('contacts.contact_id', 'like', $q);
                });
            }

            $paginator = $query->orderBy('contacts.name')->paginate((int) ($data['per_page'] ?? 20));

            return $this->success($paginator->getCollection()->map(fn ($contact) => $this->customerPayload($contact))->values(), $this->paginationMeta($paginator));
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_customers']);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        try {
            $user = $request->user();
            if (! $user->can('customer.create')) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            DB::beginTransaction();
            $output = $this->contactUtil->createNewContact([
                'type' => 'customer',
                'contact_type' => 'individual',
                'name' => $data['name'],
                'first_name' => $data['name'],
                'mobile' => $data['mobile'],
                'email' => $data['email'] ?? null,
                'business_id' => $user->business_id,
                'created_by' => $user->id,
                'opening_balance' => 0,
            ]);
            event(new ContactCreatedOrModified($output['data']->toArray(), 'added'));
            DB::commit();

            return $this->success($this->customerPayload($output['data']), [], 201);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return $this->serverError($exception, ['action' => 'mobile_customer_create']);
        }
    }
}
