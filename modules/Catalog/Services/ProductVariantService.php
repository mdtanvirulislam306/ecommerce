<?php

namespace Modules\Catalog\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Models\ProductVariantAttributeValue;

class ProductVariantService extends Service
{
    public function listPaginated(?string $search = null, ?int $productId = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return ProductVariant::query()
            ->with(['product:id,name', 'attributeValues.attribute:id,name', 'attributeValues.option:id,value'])
            ->when($productId, fn ($query, $productId) => $query->where('product_id', $productId))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%"));
            }))
            ->orderBy('sort_order')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data) {
            $variant = $product->variants()->create([
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'name' => $data['name'] ?? null,
                'weight' => $data['weight'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            if (! empty($data['attributes'])) {
                $this->syncAttributes($variant, $data['attributes']);
            }

            return $variant->load('attributeValues.attribute', 'attributeValues.option');
        });
    }

    public function update(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data) {
            $variant->update([
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? $variant->barcode,
                'name' => $data['name'] ?? $variant->name,
                'weight' => $data['weight'] ?? $variant->weight,
                'is_active' => $data['is_active'] ?? $variant->is_active,
                'sort_order' => $data['sort_order'] ?? $variant->sort_order,
            ]);

            if (array_key_exists('attributes', $data)) {
                $this->syncAttributes($variant, $data['attributes'] ?? []);
            }

            return $variant->fresh()->load('attributeValues.attribute', 'attributeValues.option');
        });
    }

    public function delete(ProductVariant $variant): void
    {
        DB::transaction(fn () => $variant->delete());
    }

    /**
     * @param  list<array{attribute_id: int, attribute_option_id: int}>  $attributes
     */
    private function syncAttributes(ProductVariant $variant, array $attributes): void
    {
        $variant->attributeValues()->delete();

        foreach ($attributes as $attribute) {
            if (empty($attribute['attribute_id']) || empty($attribute['attribute_option_id'])) {
                continue;
            }

            ProductVariantAttributeValue::query()->create([
                'product_variant_id' => $variant->id,
                'attribute_id' => $attribute['attribute_id'],
                'attribute_option_id' => $attribute['attribute_option_id'],
            ]);
        }
    }
}
