<?php

namespace Modules\Hrm\Http\Requests;

use App\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $existingUser = null;

        if ($this->filled('existing_user_id')) {
            $existingUser = User::find($this->input('existing_user_id'));
        }

        if ($this->has('gender')) {
            $this->merge([
                'gender' => strtolower((string) $this->input('gender')),
            ]);
        }

        if ($existingUser) {
            $firstName = trim((string) ($existingUser->first_name ?? ''));
            $lastName = trim((string) ($existingUser->last_name ?? ($existingUser->surname ?? '')));
            $email = $existingUser->email ?? null;
            $phone = $existingUser->contact_no ?? $existingUser->contact_number ?? $existingUser->alt_number ?? null;
            $gender = strtolower((string) ($existingUser->gender ?? $this->input('gender') ?? 'male'));
            if (! in_array($gender, ['male', 'female', 'other'], true)) {
                $gender = 'male';
            }

            $this->merge([
                'firstname' => $this->input('firstname') ?: ($firstName ?: ($existingUser->username ?: 'User')),
                'lastname' => $this->input('lastname') ?: ($lastName ?: 'User'),
                'email' => $this->input('email') ?: $email,
                'phone' => $this->input('phone') ?: $phone,
                'gender' => $this->input('gender') ?: $gender,
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
        $existingUserId = $this->input('existing_user_id');
        $ignoredEmployeeId = null;

        if ($existingUserId && Schema::hasTable('employees')) {
            $user = User::find($existingUserId);
            if ($user) {
                $matchQuery = \App\Models\Employee::query();
                $matchQuery->where(function ($query) use ($user) {
                    if (! empty($user->email)) {
                        $query->orWhere('email', $user->email);
                    }
                    if (! empty($user->username)) {
                        $query->orWhere('username', $user->username);
                    }
                });
                $ignoredEmployeeId = optional($matchQuery->orderByDesc('id')->first())->id;
            }
        }

        return [
            'existing_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'firstname'        => 'required|string|max:100',
            'lastname'         => 'required|string|max:100',
            'gender'           => 'required|in:male,female,other',
            'email'            => [
                'nullable',
                'email',
                'max:191',
                Rule::unique('employees', 'email')->ignore($ignoredEmployeeId),
            ],
            'phone'            => 'nullable|string|max:30',
            'company_id'       => 'required|integer',
            'department_id'    => 'nullable|integer',
            'designation_id'   => 'nullable|integer',
            'office_shift_id'  => 'nullable|integer',
            'joining_date'     => 'nullable|date',
            'birth_date'       => 'nullable|date|before:today',
            'leaving_date'     => 'nullable|date|after_or_equal:joining_date',
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
            'existing_user_id.exists' => 'The selected user could not be found.',
            'firstname.required'  => 'First name is required.',
            'lastname.required'   => 'Last name is required.',
            'gender.required'     => 'Please select a gender.',
            'email.unique'        => 'This email address is already assigned to another employee.',
            'company_id.required' => 'Please assign the employee to a company.',
            'birth_date.before'   => 'Date of birth must be in the past.',
        ];
    }
}
