<?php

namespace Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => $this->input('description') ?: null,
            'rules' => $this->input('rules') ?: null,
            'customer_count' => $this->input('customer_count') ?: 0,
            'customer_segment_id' => $this->input('customer_segment_id') ?: null,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'customer_segment_id' => ['nullable', 'integer', 'exists:customer_segments,id'],
            'rules' => ['nullable', 'array'],
            'customer_count' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
