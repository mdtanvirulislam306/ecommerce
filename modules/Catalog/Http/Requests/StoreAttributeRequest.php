<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Enums\AttributeInputType;
use Modules\Catalog\Enums\AttributeType;

class StoreAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order') ?? 0,
            'options' => $this->input('options') ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:60', 'unique:attributes,code'],
            'type' => ['required', Rule::enum(AttributeType::class)],
            'input_type' => ['required', Rule::enum(AttributeInputType::class)],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'options' => ['nullable', 'array'],
            'options.*.value' => ['required', 'string', 'max:255'],
            'options.*.code' => ['nullable', 'string', 'max:60'],
        ];
    }
}
