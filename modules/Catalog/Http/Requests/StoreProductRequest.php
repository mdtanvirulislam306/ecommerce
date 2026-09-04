<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\ProductType;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Catalog\Services\CatalogSettingsService;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'brand_id' => $this->input('brand_id') ?: null,
            'primary_category_id' => $this->input('primary_category_id') ?: null,
            'unit_id' => $this->input('unit_id') ?: null,
            'product_family_id' => $this->input('product_family_id') ?: null,
            'category_ids' => $this->input('category_ids') ?? [],
            'collection_ids' => $this->input('collection_ids') ?? [],
            'informational_attributes' => $this->input('informational_attributes') ?? [],
            'variants' => $this->input('variants') ?? [],
            'media_library_ids' => $this->input('media_library_ids') ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->input('type', ProductType::Simple->value);
        $settings = app(CatalogSettingsService::class)->get();

        return [
            'type' => ['required', Rule::enum(ProductType::class)],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'internal_code' => ['nullable', 'string', 'max:60'],
            'sku' => [
                Rule::requiredIf($type === ProductType::Simple->value),
                'nullable',
                'string',
                'max:80',
                'unique:products,sku',
            ],
            'barcode' => ['nullable', 'string', 'max:80'],
            'brand_id' => [
                $settings->require_brand_on_create ? 'required' : 'nullable',
                'integer',
                'exists:brands,id',
            ],
            'primary_category_id' => [
                $settings->require_primary_category_on_create ? 'required' : 'nullable',
                'integer',
                'exists:categories,id',
            ],
            'unit_id' => [
                $settings->require_unit_on_create ? 'required' : 'nullable',
                'integer',
                'exists:units,id',
            ],
            'product_family_id' => ['nullable', 'integer', 'exists:product_families,id'],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'publication_status' => ['nullable', Rule::enum(PublicationStatus::class)],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'collection_ids' => ['nullable', 'array'],
            'collection_ids.*' => ['integer', 'exists:collections,id'],
            'informational_attributes' => ['nullable', 'array'],
            'informational_attributes.*.attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'informational_attributes.*.attribute_option_id' => ['nullable', 'integer', 'exists:attribute_options,id'],
            'informational_attributes.*.value' => ['nullable', 'string', 'max:1000'],
            'variants' => [
                Rule::requiredIf($type === ProductType::Variant->value),
                'nullable',
                'array',
                Rule::when($type === ProductType::Variant->value, 'min:1'),
            ],
            'variants.*.sku' => ['required', 'string', 'max:80', 'distinct', 'unique:product_variants,sku'],
            'variants.*.barcode' => ['nullable', 'string', 'max:80'],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.attributes' => ['nullable', 'array'],
            'variants.*.attributes.*.attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'variants.*.attributes.*.attribute_option_id' => ['required', 'integer', 'exists:attribute_options,id'],
            'media' => ['nullable', 'array', 'max:'.$settings->max_media_per_product],
            'media.*' => ['file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'media_library_ids' => ['nullable', 'array'],
            'media_library_ids.*' => ['integer'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'opening_stock' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
