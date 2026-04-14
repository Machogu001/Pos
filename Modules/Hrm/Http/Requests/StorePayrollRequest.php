<?php

namespace Modules\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->can('hrm.access') || $user->can('hrm.payrolls'));
    }

    public function rules(): array
    {
        return [
            'company_id'    => 'required|integer',
            'employee_id'   => 'required|array|min:1',
            'employee_id.*' => 'required|integer|exists:employees,id',
            'period_start'  => 'required|date',
            'period_end'    => 'required|date|after_or_equal:period_start',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required'   => 'Please select a company.',
            'employee_id.required'  => 'Please select at least one employee.',
            'employee_id.min'       => 'Please select at least one employee.',
            'period_start.required' => 'Payroll period start date is required.',
            'period_end.required'   => 'Payroll period end date is required.',
            'period_end.after_or_equal' => 'Period end must be on or after period start.',
        ];
    }
}
