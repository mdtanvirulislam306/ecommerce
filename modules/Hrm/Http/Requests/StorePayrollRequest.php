<?php

namespace Modules\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:hrm_employees,id'],
            'period' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
