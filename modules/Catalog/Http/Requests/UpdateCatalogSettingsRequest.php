<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\PublicationStatus;

class UpdateCatalogSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'default_unit_id' => $this->input('default_unit_id') ?: null,
            'sku_prefix' => $this->input('sku_prefix') ?: null,
            'require_brand_on_create' => $this->boolean('require_brand_on_create'),
            'require_primary_category_on_create' => $this->boolean('require_primary_category_on_create'),
            'require_unit_on_create' => $this->boolean('require_unit_on_create'),
            'auto_submit_for_review_on_create' => $this->boolean('auto_submit_for_review_on_create'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'default_product_status' => ['required', Rule::enum(ProductStatus::class)],
            'default_publication_status' => ['required', Rule::enum(PublicationStatus::class)],
            'require_brand_on_create' => ['boolean'],
            'require_primary_category_on_create' => ['boolean'],
            'require_unit_on_create' => ['boolean'],
            'auto_submit_for_review_on_create' => ['boolean'],
            'sku_prefix' => ['nullable', 'string', 'max:20'],
            'default_unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'max_media_per_product' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
