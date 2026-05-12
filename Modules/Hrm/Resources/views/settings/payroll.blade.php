@extends('layouts.app')

@section('title', __('ui.payroll_accounting_setup'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.payroll_accounting_setup'),
    'subtitle' => __('ui.choose_the_chart_of_accounts_used_when_payroll_is_posted_automatically'),
    'actions' => '<a href="'.route('hrm.settings.modules.edit').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_modules') .'</a>'
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
            <h3 class="box-title">{{ __('ui.payroll_posting_accounts') }}</h3>
        </div>
        <form method="POST" action="{{ route('hrm.settings.payroll.update') }}">
            @csrf
            <div class="box-body">
                <p class="help-block">{{ __('ui.choose_the_accounts_used_when_salaries_are_processed_payroll_will_debit_the_expense_account_and_credit_the_clearing_account_automatically') }}</p>

                <div class="form-group">
                    <label for="payroll_expense_account_id">{{ __('ui.payroll_expense_account') }}</label>
                    <select name="payroll_expense_account_id" id="payroll_expense_account_id" class="form-control" required>
                        <option value="">{{ __('ui.select_expense_account') }}</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('payroll_expense_account_id', $settings->payroll_expense_account_id ?? null) == $account->id ? 'selected' : '' }}>
                                {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="payroll_clearing_account_id">{{ __('ui.payroll_clearing_account') }}</label>
                    <select name="payroll_clearing_account_id" id="payroll_clearing_account_id" class="form-control" required>
                        <option value="">{{ __('ui.select_clearing_account') }}</option>
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
                        {{ __('ui.automatically_post_payroll_to_the_chart_of_accounts_when_saved') }}
                    </label>
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary">{{ __('ui.save_payroll_settings') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection