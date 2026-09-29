<?php

namespace Modules\Catalog\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Catalog\Models\ProductFamily;

class UpdateProductFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ProductFamily $family */
        $family = $this->route('family');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', TenantRule::unique('product_families', 'slug')->ignore($family->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
