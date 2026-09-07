<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\ProductType;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Catalog\Models\Product;

class UpdateProductRequest extends FormRequest
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
            'slug' => filled($this->input('slug')) ? $this->input('slug') : null,
            'internal_code' => filled($this->input('internal_code')) ? $this->input('internal_code') : null,
            'sku' => filled($this->input('sku')) ? $this->input('sku') : null,
            'barcode' => filled($this->input('barcode')) ? $this->input('barcode') : null,
            'category_ids' => $this->input('category_ids') ?? [],
            'collection_ids' => $this->input('collection_ids') ?? [],
            'informational_attributes' => $this->input('informational_attributes') ?? [],
            'variants' => $this->input('variants') ?? [],
            'remove_media_ids' => $this->input('remove_media_ids') ?? [],
            'media_library_ids' => $this->input('media_library_ids') ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');
        $type = $this->input('type', $product->type?->value ?? ProductType::Simple->value);

        return [
            'type' => ['required', Rule::enum(ProductType::class)],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'internal_code' => ['nullable', 'string', 'max:60'],
            'sku' => ['nullable', 'string', 'max:80'],
            'barcode' => ['nullable', 'string', 'max:80'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'primary_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
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
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.sku' => ['nullable', 'string', 'max:80', 'distinct'],
            'variants.*.barcode' => ['nullable', 'string', 'max:80', 'distinct'],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.attributes' => ['nullable', 'array'],
            'variants.*.attributes.*.attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'variants.*.attributes.*.attribute_option_id' => ['required', 'integer', 'exists:attribute_options,id'],
            'variants.*.media_library_ids' => ['nullable', 'array', 'max:1'],
            'variants.*.media_library_ids.*' => ['integer'],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*' => ['file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'media_library_ids' => ['nullable', 'array'],
            'media_library_ids.*' => ['integer'],
            'remove_media_ids' => ['nullable', 'array'],
            'remove_media_ids.*' => ['integer', 'exists:product_media,id'],
            'primary_media_id' => ['nullable', 'integer', 'exists:product_media,id'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'opening_stock' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
