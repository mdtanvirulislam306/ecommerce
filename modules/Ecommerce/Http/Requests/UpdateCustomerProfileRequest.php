<?php

namespace Modules\Ecommerce\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => $this->input('phone') ?: null,
            'default_address' => $this->input('default_address') ?: null,
            'default_district' => $this->input('default_district') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                TenantRule::unique('customer_accounts', 'email')->ignore($this->user('customer')->id),
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'default_address' => ['nullable', 'string', 'max:1000'],
            'default_district' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Another account already uses this email.',
        ];
    }
}
