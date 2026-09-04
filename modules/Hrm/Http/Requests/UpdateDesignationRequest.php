<?php

namespace Modules\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDesignationRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:40', 'unique:hrm_designations,code'],
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:hrm_departments,id'],
            'is_active' => ['boolean'],
        ];
    }
}
