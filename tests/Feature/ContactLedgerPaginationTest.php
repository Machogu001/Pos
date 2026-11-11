<?php

namespace Tests\Feature;

use App\Transaction;
use App\TransactionPayment;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContactLedgerPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::connection()->getPdo()->sqliteCreateFunction('IF', fn ($condition, $yes, $no) => $condition ? $yes : $no, 3);

        Schema::create('business', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Test Business');
            $table->text('custom_labels')->nullable();
        });
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('type');
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('business_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('name');
        });
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('location_id');
            $table->string('type');
            $table->string('sub_type')->nullable();
            $table->string('status')->default('final');
            $table->dateTime('transaction_date');
            $table->decimal('final_total', 22, 4);
            $table->string('payment_status')->default('partial');
            $table->string('invoice_no')->nullable();
            $table->string('ref_no')->nullable();
            $table->text('additional_notes')->nullable();
            $table->string('pay_term_type')->nullable();
            $table->integer('pay_term_number')->nullable();
        });
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('transaction_id')->nullable();
            $table->unsignedInteger('payment_for');
            $table->unsignedInteger('parent_id')->nullable();
            $table->decimal('amount', 22, 4);
            $table->boolean('is_return')->default(false);
            $table->boolean('is_advance')->default(false);
            $table->string('payment_type')->nullable();
            $table->string('method')->default('cash');
            $table->dateTime('paid_on');
            $table->string('payment_ref_no')->nullable();
            $table->text('note')->nullable();
        });
        foreach (['transaction_sell_lines', 'purchase_lines'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('transaction_id');
            });
        }

        DB::table('business')->insert(['id' => 1]);
        DB::table('contacts')->insert(['id' => 1, 'business_id' => 1, 'type' => 'customer', 'name' => 'Walk-In Customer']);
        DB::table('business_locations')->insert(['id' => 1, 'business_id' => 1, 'name' => 'Test Location']);
        $this->withSession([
            'user.business_id' => 1,
            'business' => ['date_format' => 'd/m/Y', 'currency_precision' => 2],
            'currency' => ['symbol' => 'KSh', 'decimal_separator' => '.', 'thousand_separator' => ','],
        ]);
        request()->setLaravelSession($this->app['session.store']);
        Carbon::setTestNow('2026-11-03 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_large_ledger_only_hydrates_the_requested_page_and_keeps_complete_totals(): void
    {
        $this->seedSales(5000);
        $transactions = 0;
        $payments = 0;
        Transaction::retrieved(function ($model) use (&$transactions) {
            $transactions += $model->id ? 1 : 0;
        });
        TransactionPayment::retrieved(function ($model) use (&$payments) {
            $payments += $model->id ? 1 : 0;
        });

        $first = $this->ledger('format_1', 1, 50);
        $this->assertCount(50, $first['ledger']);
        $this->assertEquals(10000, $first['pagination']->total());
        $this->assertEquals(25, $transactions);
        $this->assertEquals(25, $payments);
        $this->assertEquals(500000, $first['total_invoice']);
        $this->assertEquals(250000, $first['total_paid']);
        $this->assertEquals(250000, $first['balance_due']);
        $this->assertEquals('INV-1', $first['ledger'][0]['ref_no']);
        $this->assertEquals('PAY-25', $first['ledger'][49]['ref_no']);

        $second = $this->ledger('format_1', 2, 50);
        $this->assertCount(50, $second['ledger']);
        $this->assertEquals('INV-26', $second['ledger'][0]['ref_no']);
        $this->assertEquals('PAY-50', $second['ledger'][49]['ref_no']);
        foreach (['total_invoice', 'total_paid', 'balance_due', 'all_total_invoice', 'all_invoice_paid', 'all_balance_due'] as $key) {
            $this->assertEquals($first[$key], $second[$key], $key);
        }

        $hundred = $this->ledger('format_1', 1, 100);
        $this->assertCount(100, $hundred['ledger']);
        $this->assertEquals('PAY-50', $hundred['ledger'][99]['ref_no']);
    }

    public function test_paginated_running_balances_match_the_full_ledger_with_equal_dates_and_returns(): void
    {
        $this->seedSales(35);
        $this->transaction(['id' => 100, 'type' => 'opening_balance', 'transaction_date' => '2026-11-02 00:00:00', 'final_total' => 40]);
        $this->transaction(['id' => 101, 'type' => 'ledger_discount', 'sub_type' => 'sell_discount', 'final_total' => 15]);
        $this->transaction(['id' => 102, 'type' => 'sell_return', 'final_total' => 20]);
        DB::table('transaction_payments')->insert([
            ['transaction_id' => 1, 'payment_for' => 1, 'amount' => 5, 'paid_on' => '2026-11-03 00:00:00', 'payment_ref_no' => 'RETURN', 'is_return' => 1, 'method' => 'cash', 'parent_id' => null],
            ['transaction_id' => 2, 'payment_for' => 1, 'amount' => 10, 'paid_on' => '2026-11-03 00:00:00', 'payment_ref_no' => 'ADVANCE', 'is_return' => 0, 'method' => 'advance', 'parent_id' => null],
            ['transaction_id' => 3, 'payment_for' => 1, 'amount' => 10, 'paid_on' => '2026-11-03 00:00:00', 'payment_ref_no' => 'ADJUSTED', 'is_return' => 0, 'method' => 'cash', 'parent_id' => 1],
        ]);

        foreach (['customer', 'supplier', 'both'] as $contact_type) {
            DB::table('contacts')->where('id', 1)->update(['type' => $contact_type]);
            foreach (['format_1', 'format_3'] as $format) {
                $full = $this->ledger($format);
                $expected = array_values(array_filter($full['ledger'], function ($row) use ($format) {
                    return $format == 'format_3' || $row['type'] != __('lang_v1.opening_balance');
                }));
                $rows = [];
                $first = $this->ledger($format, 1, 10);
                for ($page = 1; $page <= $first['pagination']->lastPage(); $page++) {
                    $result = $this->ledger($format, $page, 10);
                    $this->assertLessThanOrEqual(10, count($result['ledger']));
                    $this->assertEquals($full['total_invoice'], $result['total_invoice']);
                    $this->assertEquals($full['total_paid'], $result['total_paid']);
                    $rows = array_merge($rows, $result['ledger']);
                }
                $this->assertEquals($expected, $rows, $contact_type.' '.$format);
            }
        }
    }

    public function test_statement_pages_exclude_paid_rows_and_keep_full_aging_totals(): void
    {
        $this->seedSales(120);
        DB::table('transactions')->where('id', 1)->update(['payment_status' => 'paid']);
        DB::table('transaction_payments')->where('transaction_id', 1)->update(['amount' => 100]);

        $first = $this->ledger('format_2', 1, 50);
        $last = $this->ledger('format_2', 3, 50);
        $this->assertEquals(119, $first['pagination']->total());
        $this->assertCount(50, $first['ledger']);
        $this->assertCount(19, $last['ledger']);
        $this->assertEquals(5950, $first['aging']['amount_due']);
        $this->assertEquals(5950, $first['aging']['current_due']);
        $this->assertEquals($first['aging'], $last['aging']);
        $this->assertEquals('INV-2', $first['ledger'][0]['ref_no']);
    }

    public function test_empty_and_out_of_range_pages_are_valid_and_exports_remain_complete(): void
    {
        $empty = $this->ledger('format_1', 1, 50);
        $this->assertSame([], $empty['ledger']);
        $this->assertEquals(0, $empty['pagination']->total());

        $this->seedSales(30);
        $last = $this->ledger('format_1', 99, 25);
        $this->assertEquals(3, $last['pagination']->currentPage());
        $this->assertCount(10, $last['ledger']);
        $export = $this->ledger('format_1');
        $this->assertNull($export['pagination']);
        $this->assertCount(61, $export['ledger']);
    }

    public function test_all_ledger_formats_render_only_the_requested_records_and_pagination(): void
    {
        $this->seedSales(120);
        $contact = \App\Contact::findOrFail(1);
        foreach (['format_1' => 'ledger', 'format_2' => 'ledger_format_2', 'format_3' => 'ledger_format_3'] as $format => $view) {
            $details = $this->ledger($format, 2, 50);
            $html = view('contact.'.$view, [
                'contact' => $contact,
                'ledger_details' => $details,
                'location' => \App\BusinessLocation::findOrFail(1),
                'is_admin' => false,
            ])->render();
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new \DOMXPath($dom);
            $rows = $xpath->query('//table[@id="ledger_table"]/tbody/tr');
            $detail_rows = $format == 'format_3' ? count(array_filter($details['ledger'], fn ($row) => ($row['transaction_type'] ?? null) == 'sell')) : 0;
            $this->assertCount(50 + $detail_rows, $rows);
            $this->assertStringContainsString('id="contact_ledger_pagination"', $html);
            $this->assertStringContainsString('data-start="51"', $html);
            $this->assertStringContainsString('data-end="100"', $html);
            $this->assertStringContainsString('page=3', $html);
        }
    }

    public function test_paging_preserves_contact_location_and_date_filters(): void
    {
        $this->seedSales(30);
        $this->transaction(['id' => 100, 'type' => 'sell', 'location_id' => 2, 'final_total' => 10000]);
        $this->transaction(['id' => 101, 'type' => 'sell', 'contact_id' => 2, 'final_total' => 10000]);
        $this->transaction(['id' => 102, 'type' => 'sell', 'business_id' => 2, 'final_total' => 10000]);
        $this->transaction(['id' => 103, 'type' => 'sell', 'transaction_date' => '2026-11-04 00:00:00', 'final_total' => 10000]);
        $this->transaction(['id' => 104, 'type' => 'sell', 'status' => 'draft', 'final_total' => 10000]);

        $page = $this->ledger('format_1', 1, 50);
        $this->assertEquals(60, $page['pagination']->total());
        $this->assertEquals(3000, $page['total_invoice']);
        $this->assertEquals(1500, $page['total_paid']);
        $this->assertCount(50, $page['ledger']);
    }

    public function test_payment_aggregates_preserve_previous_and_overall_account_summaries(): void
    {
        $this->seedSales(1);
        $types = ['sell' => 200, 'purchase' => 100, 'sell_return' => 30, 'purchase_return' => 10, 'opening_balance' => 20, 'ledger_discount' => 5];
        $amounts_paid = ['sell' => 80, 'purchase' => 40, 'sell_return' => 5, 'purchase_return' => 2, 'opening_balance' => 10];
        $id = 100;
        foreach ($types as $type => $total) {
            $this->transaction(['id' => ++$id, 'type' => $type, 'final_total' => $total, 'transaction_date' => '2026-11-02 12:00:00']);
            if (isset($amounts_paid[$type])) {
                DB::table('transaction_payments')->insert(['transaction_id' => $id, 'payment_for' => 1,
                    'amount' => $amounts_paid[$type], 'paid_on' => '2026-11-02 12:00:00']);
            }
        }
        DB::table('transaction_payments')->insert(['transaction_id' => 101, 'payment_for' => 1,
            'amount' => 5, 'is_return' => 1, 'paid_on' => '2026-11-02 12:00:00']);

        $page = $this->ledger('format_1', 1, 50);
        $this->assertEquals(157, $page['beginning_balance']);
        $this->assertEquals(100, $page['total_invoice']);
        $this->assertEquals(50, $page['total_paid']);
        $this->assertEquals(207, $page['balance_due']);
        $this->assertEquals(285, $page['all_total_invoice']);
        $this->assertEquals(90, $page['all_total_purchase']);
        $this->assertEquals(130, $page['all_invoice_paid']);
        $this->assertEquals(38, $page['all_purchase_paid']);
        $this->assertEquals(202, $page['all_balance_due']);
    }

    public function test_controller_defaults_to_fifty_records_and_rejects_unbounded_page_sizes(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('user_contact_access', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('contact_id');
        });
        $user = \Mockery::mock(\App\User::class)->makePartial();
        $user->id = 1;
        $user->business_id = 1;
        $user->shouldReceive('can')->andReturn(true);
        $user->shouldReceive('hasRole')->with('Admin#1')->andReturn(true);
        auth()->setUser($user);
        $this->seedSales(60);

        $request = \Illuminate\Http\Request::create('/contacts/ledger', 'GET', [
            'contact_id' => 1, 'start_date' => '2026-11-03', 'end_date' => '2026-11-03', 'location_id' => 1,
        ]);
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);
        $controller = app(\App\Http\Controllers\ContactController::class);
        $details = $controller->getLedger()->getData()['ledger_details'];
        $this->assertCount(50, $details['ledger']);
        $this->assertEquals(50, $details['pagination']->perPage());
        $this->assertEquals(120, $details['pagination']->total());

        $request->query->set('per_page', -1);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->getLedger();
    }

    private function ledger(string $format, ?int $page = null, int $per_page = 50): array
    {
        return app(TransactionUtil::class)->getLedgerDetails(1, '2026-11-03', '2026-11-03', $format, 1, $format == 'format_3',
            $page === null ? null : ['page' => $page, 'per_page' => $per_page]);
    }

    private function seedSales(int $count): void
    {
        foreach (array_chunk(range(1, $count), 100) as $ids) {
            $sales = [];
            $payments = [];
            foreach ($ids as $id) {
                $date = Carbon::parse('2026-11-03')->addSeconds($id * 2);
                $sales[] = ['id' => $id, 'business_id' => 1, 'contact_id' => 1, 'location_id' => 1,
                    'type' => 'sell', 'transaction_date' => $date->toDateTimeString(), 'final_total' => 100, 'invoice_no' => 'INV-'.$id];
                $payments[] = ['transaction_id' => $id, 'payment_for' => 1, 'amount' => 50,
                    'paid_on' => $date->addSecond()->toDateTimeString(), 'payment_ref_no' => 'PAY-'.$id];
            }
            DB::table('transactions')->insert($sales);
            DB::table('transaction_payments')->insert($payments);
        }
    }

    private function transaction(array $attributes): void
    {
        DB::table('transactions')->insert(array_merge([
            'business_id' => 1, 'contact_id' => 1, 'location_id' => 1,
            'transaction_date' => '2026-11-03 00:00:00',
        ], $attributes));
    }
}
