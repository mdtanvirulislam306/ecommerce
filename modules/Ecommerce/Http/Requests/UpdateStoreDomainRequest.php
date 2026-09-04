<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_primary' => $this->boolean('is_primary'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $domainId = $this->route('storeDomain')?->id ?? $this->route('storeDomain');

        return [
            'domain' => ['required', 'string', 'max:255', Rule::unique('store_domains', 'domain')->ignore($domainId)],
            'is_primary' => ['boolean'],
            'is_active' => ['boolean'],
            'ssl_status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
