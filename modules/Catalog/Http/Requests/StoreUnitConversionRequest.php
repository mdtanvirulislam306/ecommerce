<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitConversionRequest extends FormRequest
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
            'from_unit_id' => ['required', 'integer', 'exists:units,id'],
            'to_unit_id' => [
                'required',
                'integer',
                'exists:units,id',
                'different:from_unit_id',
            ],
            'factor' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
