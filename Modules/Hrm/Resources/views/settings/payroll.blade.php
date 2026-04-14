@extends('layouts.app')

@section('title', 'Payroll Accounting Setup')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Payroll Accounting Setup',
    'subtitle' => 'Choose the chart of accounts used when payroll is posted automatically.',
    'actions' => '<a href="'.route('hrm.settings.modules.edit').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Modules</a>'
])

<section class="content">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Payroll Posting Accounts</h3>
        </div>
        <form method="POST" action="{{ route('hrm.settings.payroll.update') }}">
            @csrf
            <div class="box-body">
                <p class="help-block">Choose the accounts used when salaries are processed. Payroll will debit the expense account and credit the clearing account automatically.</p>

                <div class="form-group">
                    <label for="payroll_expense_account_id">Payroll Expense Account</label>
                    <select name="payroll_expense_account_id" id="payroll_expense_account_id" class="form-control" required>
                        <option value="">-- Select Expense Account --</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('payroll_expense_account_id', $settings->payroll_expense_account_id ?? null) == $account->id ? 'selected' : '' }}>
                                {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="payroll_clearing_account_id">Payroll Clearing Account</label>
                    <select name="payroll_clearing_account_id" id="payroll_clearing_account_id" class="form-control" required>
                        <option value="">-- Select Clearing Account --</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('payroll_clearing_account_id', $settings->payroll_clearing_account_id ?? null) == $account->id ? 'selected' : '' }}>
                                {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="payroll_auto_post" value="1" {{ old('payroll_auto_post', $settings->payroll_auto_post ?? true) ? 'checked' : '' }}>
                        Automatically post payroll to the chart of accounts when saved
                    </label>
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Save Payroll Settings</button>
            </div>
        </form>
    </div>
</section>
@endsection