<?php

namespace Modules\Ecommerce\Services;

use App\Core\Contracts\PriceResolver;
use App\Core\Contracts\StockAvailability;
use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontCatalogService extends Service
{
    public function __construct(
        private readonly PriceResolver $prices,
        private readonly StockAvailability $stock,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function publishedProducts(int $limit = 48): array
    {
        $rows = DB::table('products')
            ->leftJoin('product_storefront_settings as storefront', 'products.id', '=', 'storefront.product_id')
            ->where('products.publication_status', 'published')
            ->where('products.status', '!=', 'archived')
            ->orderByDesc(DB::raw('COALESCE(storefront.storefront_sort_order, 0)'))
            ->orderBy('products.name')
            ->limit($limit)
            ->get([
                'products.id',
                'products.name',
                'products.slug',
                'products.sku',
                'products.type',
                DB::raw('COALESCE(storefront.is_featured, 0) as is_featured'),
            ]);

        $imageMap = $this->primaryImageMap($rows->pluck('id')->all());

        return $rows->map(function ($row) use ($imageMap) {
            $resolved = $this->prices->resolve(productId: $row->id, quantity: 1);
            $available = $this->stock->available($row->id);

            return [
                'id' => $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'sku' => $row->sku,
                'type' => $row->type,
                'is_featured' => (bool) $row->is_featured,
                'image_url' => $imageMap[$row->id] ?? null,
                'price' => $resolved['resolved'] ? $resolved['price'] : null,
                'currency' => $resolved['currency'] ?? 'BDT',
                'price_message' => $resolved['resolved'] ? null : ($resolved['message'] ?? 'Price unavailable'),
                'stock_available' => $available['available'],
                'in_stock' => (float) $available['available'] > 0,
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function productBySlug(string $slug): array
    {
        $product = DB::table('products')
            ->leftJoin('product_storefront_settings as storefront', 'products.id', '=', 'storefront.product_id')
            ->where('products.slug', $slug)
            ->where('products.publication_status', 'published')
            ->where('products.status', '!=', 'archived')
            ->first([
                'products.id',
                'products.name',
                'products.slug',
                'products.sku',
                'products.type',
                'products.description',
                DB::raw('COALESCE(storefront.is_featured, 0) as is_featured'),
            ]);

        if ($product === null) {
            throw new NotFoundHttpException('Product not found.');
        }

        $productId = (int) $product->id;

        $media = DB::table('product_media')
            ->where('product_id', $productId)
            ->orderBy('sort_order')
            ->get(['id', 'path', 'alt', 'is_primary', 'sort_order', 'product_variant_id'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'url' => '/storage/'.ltrim(str_replace('\\', '/', $row->path), '/'),
                'alt' => $row->alt,
                'is_primary' => (bool) $row->is_primary,
                'sort_order' => $row->sort_order,
                'product_variant_id' => $row->product_variant_id,
            ])
            ->all();

        $productMedia = array_values(array_filter($media, fn ($m) => $m['product_variant_id'] === null));

        $variants = DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'sku', 'name', 'barcode']);

        $attributeRows = DB::table('product_variant_attribute_values as vv')
            ->join('attributes as a', 'a.id', '=', 'vv.attribute_id')
            ->join('attribute_options as o', 'o.id', '=', 'vv.attribute_option_id')
            ->whereIn('vv.product_variant_id', $variants->pluck('id')->all() ?: [0])
            ->get([
                'vv.product_variant_id',
                'vv.attribute_id',
                'a.name as attribute_name',
                'vv.attribute_option_id',
                'o.value as option_value',
            ])
            ->groupBy('product_variant_id');

        $formattedVariants = $variants->map(function ($variant) use ($media, $attributeRows, $productId) {
            $variantMedia = array_values(array_filter(
                $media,
                fn ($m) => (int) $m['product_variant_id'] === (int) $variant->id,
            ));
            $primary = collect($variantMedia)->firstWhere('is_primary', true) ?? ($variantMedia[0] ?? null);
            $resolved = $this->prices->resolve(
                productId: $productId,
                quantity: 1,
                productVariantId: (int) $variant->id,
            );
            $available = $this->stock->available($productId, (int) $variant->id);
            $attrs = ($attributeRows[$variant->id] ?? collect())->map(fn ($row) => [
                'attribute_id' => $row->attribute_id,
                'attribute_name' => $row->attribute_name,
                'attribute_option_id' => $row->attribute_option_id,
                'option_value' => $row->option_value,
            ])->values()->all();

            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'barcode' => $variant->barcode,
                'attributes' => $attrs,
                'media' => $variantMedia,
                'image_url' => $primary['url'] ?? null,
                'price' => $resolved['resolved'] ? $resolved['price'] : null,
                'currency' => $resolved['currency'] ?? 'BDT',
                'price_message' => $resolved['resolved'] ? null : ($resolved['message'] ?? 'Price unavailable'),
                'stock_available' => $available['available'],
                'in_stock' => (float) $available['available'] > 0,
            ];
        })->all();

        $resolved = $this->prices->resolve(productId: $productId, quantity: 1);
        $available = $this->stock->available($productId);
        $primaryProductImage = collect($productMedia)->firstWhere('is_primary', true) ?? ($productMedia[0] ?? null);

        return [
            'id' => $productId,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'type' => $product->type,
            'description' => $product->description,
            'is_featured' => (bool) $product->is_featured,
            'media' => $productMedia,
            'image_url' => $primaryProductImage['url'] ?? null,
            'variants' => $formattedVariants,
            'price' => $resolved['resolved'] ? $resolved['price'] : null,
            'currency' => $resolved['currency'] ?? 'BDT',
            'price_message' => $resolved['resolved'] ? null : ($resolved['message'] ?? 'Price unavailable'),
            'stock_available' => $available['available'],
            'in_stock' => (float) $available['available'] > 0,
        ];
    }

    /**
     * @param  list<int>  $productIds
     * @return array<int, string>
     */
    private function primaryImageMap(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = DB::table('product_media')
            ->whereIn('product_id', $productIds)
            ->whereNull('product_variant_id')
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->get(['product_id', 'path']);

        $map = [];
        foreach ($rows as $row) {
            if (isset($map[$row->product_id])) {
                continue;
            }
            $map[$row->product_id] = '/storage/'.ltrim(str_replace('\\', '/', $row->path), '/');
        }

        return $map;
    }
}
