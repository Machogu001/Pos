<?php

namespace Modules\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('gender')) {
            $this->merge([
                'gender' => strtolower((string) $this->input('gender')),
            ]);
        }
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->can('hrm.access') || $user->can('hrm.employees'));
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee');

        return [
            'firstname'        => 'required|string|max:100',
            'lastname'         => 'required|string|max:100',
            'gender'           => 'required|in:male,female,other',
            'email'            => "nullable|email|max:191|unique:employees,email,{$employeeId}",
            'phone'            => 'nullable|string|max:30',
            'company_id'       => 'required|integer',
            'department_id'    => 'nullable|integer',
            'designation_id'   => 'nullable|integer',
            'office_shift_id'  => 'nullable|integer',
            'joining_date'     => 'nullable|date',
            'birth_date'       => 'nullable|date|before:today',
            'leaving_date'     => 'nullable|date',
            'total_leave'      => 'nullable|integer|min:0|max:365',
            'remaining_leave'  => 'nullable|integer|min:0|max:365',
            'employment_type'  => 'nullable|string|max:50',
            'basic_salary'     => 'nullable|numeric|min:0',
            'hourly_rate'      => 'nullable|numeric|min:0',
            'marital_status'   => 'nullable|string|max:30',
            'country'          => 'nullable|string|max:100',
            'address'          => 'nullable|string|max:500',
            'city'             => 'nullable|string|max:100',
            'province'         => 'nullable|string|max:100',
            'zipcode'          => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'firstname.required'  => 'First name is required.',
            'lastname.required'   => 'Last name is required.',
            'gender.required'     => 'Please select a gender.',
            'email.unique'        => 'This email address is already assigned to another employee.',
            'company_id.required' => 'Please assign the employee to a company.',
            'birth_date.before'   => 'Date of birth must be in the past.',
        ];
    }
}
