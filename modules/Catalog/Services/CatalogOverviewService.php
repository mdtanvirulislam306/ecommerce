<?php

namespace Modules\Catalog\Services;

use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\ProductType;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Catalog\Models\Attribute;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Collection;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductFamily;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Models\Unit;

class CatalogOverviewService extends Service
{
    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $productCounts = Product::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $typeCounts = Product::query()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $publicationCounts = Product::query()
            ->selectRaw('publication_status, COUNT(*) as total')
            ->groupBy('publication_status')
            ->pluck('total', 'publication_status');

        return [
            'products' => [
                'total' => Product::query()->count(),
                'draft' => (int) ($productCounts[ProductStatus::Draft->value] ?? 0),
                'pending_review' => (int) ($productCounts[ProductStatus::PendingReview->value] ?? 0),
                'approved' => (int) ($productCounts[ProductStatus::Approved->value] ?? 0),
                'active' => (int) ($productCounts[ProductStatus::Active->value] ?? 0),
                'archived' => (int) ($productCounts[ProductStatus::Archived->value] ?? 0),
                'simple' => (int) ($typeCounts[ProductType::Simple->value] ?? 0),
                'variant' => (int) ($typeCounts[ProductType::Variant->value] ?? 0),
            ],
            'publication' => [
                'published' => (int) ($publicationCounts[PublicationStatus::Published->value] ?? 0),
                'not_published' => (int) ($publicationCounts[PublicationStatus::NotPublished->value] ?? 0),
                'unpublished' => (int) ($publicationCounts[PublicationStatus::Unpublished->value] ?? 0),
            ],
            'master_data' => [
                'brands' => Brand::query()->count(),
                'categories' => Category::query()->count(),
                'attributes' => Attribute::query()->count(),
                'collections' => Collection::query()->count(),
                'families' => ProductFamily::query()->count(),
                'units' => Unit::query()->count(),
                'variants' => ProductVariant::query()->count(),
            ],
            'media' => [
                'products_with_media' => DB::table('product_media')
                    ->distinct('product_id')
                    ->count('product_id'),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentProducts(int $limit = 8): array
    {
        return Product::query()
            ->with(['brand:id,name'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'status', 'publication_status', 'type', 'brand_id', 'updated_at'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'status' => $product->status->value,
                'status_label' => $product->status->label(),
                'publication_status' => $product->publication_status->value,
                'publication_status_label' => $product->publication_status->label(),
                'type' => $product->type->value,
                'brand' => $product->brand?->name,
                'updated_at' => $product->updated_at?->toIso8601String(),
            ])
            ->all();
    }
}
