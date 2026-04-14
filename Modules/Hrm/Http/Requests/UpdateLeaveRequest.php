<?php

namespace Modules\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->can('hrm.access') || $user->can('hrm.leaves') || $user->can('leave.update'));
    }

    public function rules(): array
    {
        return [
            'employee_id'   => 'required|integer|exists:employees,id',
            'company_id'    => 'required|integer|exists:companies,id',
            'department_id' => 'required|integer|exists:departments,id',
            'leave_type_id' => 'required|integer|exists:leave_types,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'nullable|string|max:1000',
            'status'        => 'required|in:pending,approved,rejected',
            'half_day'      => 'nullable|boolean',
            'attachment'    => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required'   => 'Please select an employee.',
            'company_id.required'    => 'Please select a company.',
            'department_id.required' => 'Please select a department.',
            'leave_type_id.required' => 'Please select a leave type.',
            'start_date.required'    => 'Start date is required.',
            'end_date.required'      => 'End date is required.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
            'attachment.mimes'       => 'Attachment must be a JPEG, PNG, or PDF file.',
            'attachment.max'         => 'Attachment must not exceed 4 MB.',
        ];
    }
}
