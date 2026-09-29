<?php

namespace Modules\Commerce\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => filled($this->input('code')) ? $this->input('code') : null,
            'price_list_id' => $this->input('price_list_id') ?: null,
            'is_active' => $this->boolean('is_active', true),
            'sort_order' => $this->input('sort_order') ?? 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:40', TenantRule::unique('customer_groups', 'code')],
            'description' => ['nullable', 'string'],
            'price_list_id' => ['nullable', 'integer', 'exists:price_lists,id'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
