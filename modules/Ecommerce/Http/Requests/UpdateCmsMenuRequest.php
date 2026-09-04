<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCmsMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'items' => $this->parseItems(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:50'],
            'items' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<int, mixed>|null
     */
    private function parseItems(): ?array
    {
        $items = $this->input('items');

        if (is_string($items)) {
            $decoded = json_decode($items, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        return is_array($items) ? $items : null;
    }
}
