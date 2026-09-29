<?php

namespace Modules\Crm\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'email' => $this->input('email') ?: null,
            'phone' => $this->input('phone') ?: null,
            'company' => $this->input('company') ?: null,
            'address' => $this->input('address') ?: null,
            'code' => $this->input('code') ?: null,
            'customer_group_id' => $this->input('customer_group_id') ?: null,
            'notes' => $this->input('notes') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:40', TenantRule::unique('customers', 'code')],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'customer_group_id' => ['nullable', 'integer', 'exists:customer_groups,id'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
