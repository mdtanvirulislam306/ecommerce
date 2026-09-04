<?php

namespace Modules\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryStructureRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'employee_id' => ['nullable', 'exists:hrm_employees,id'],
            'designation_id' => ['nullable', 'exists:hrm_designations,id'],
            'components' => ['nullable'],
            'is_active' => ['boolean'],
        ];
    }
}
