<?php

namespace Modules\Ecommerce\Services;

use App\Core\Contracts\PriceResolver;
use App\Core\Contracts\StockAvailability;
use App\Core\Module\ModuleManager;
use App\Core\Support\Service;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontCatalogService extends Service
{
    public function __construct(
        private readonly PriceResolver $prices,
        private readonly StockAvailability $stock,
    ) {}

    /** @var array<int, list<int>>|null */
    private ?array $activeChildrenByParent = null;

    /**
     * @return array{
     *     categories: list<array<string, mixed>>,
     *     story_groups: list<array<string, mixed>>,
     *     trending: list<array<string, mixed>>,
     *     homepage: list<array<string, mixed>>,
     *     best_selling: list<array<string, mixed>>,
     *     search: string|null,
     *     search_results: list<array<string, mixed>>|null,
     *     category: array{id: int, name: string, slug: string, image_url: string|null, parent_slug: string|null, parent_name: string|null, root_slug: string}|null,
     *     category_products: list<array<string, mixed>>|null,
     *     child_categories: list<array<string, mixed>>
     * }
     */
    public function homepage(?string $search = null, ?string $categorySlug = null): array
    {
        $search = $search !== null ? trim($search) : null;
        $search = $search === '' ? null : $search;
        $categorySlug = $categorySlug !== null ? trim($categorySlug) : null;
        $categorySlug = $categorySlug === '' ? null : $categorySlug;

        $category = $categorySlug !== null ? $this->findActiveCategoryBySlug($categorySlug) : null;

        return [
            'categories' => $this->activeCategories(),
            'story_groups' => $this->activeStoryGroups(),
            'trending' => $this->featuredProducts(6),
            'homepage' => $this->homepageProducts(6),
            'best_selling' => $this->publishedProducts(12),
            'search' => $search,
            'search_results' => $search !== null ? $this->searchProducts($search, 24) : null,
            'category' => $category,
            'category_products' => $category !== null
                ? $this->productsByCategoryIds($this->descendantCategoryIds((int) $category['id']))
                : null,
            'child_categories' => $category !== null
                ? $this->childCategories((int) $category['id'])
                : [],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function publishedProducts(int $limit = 48): array
    {
        $rows = $this->baseProductQuery()
            ->orderByDesc(DB::raw('COALESCE(storefront.storefront_sort_order, 0)'))
            ->orderBy('products.name')
            ->limit($limit)
            ->get($this->productSelectColumns());

        return $this->formatProductCollection($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function featuredProducts(int $limit = 6): array
    {
        $rows = $this->baseProductQuery()
            ->where('storefront.is_featured', true)
            ->orderByDesc(DB::raw('COALESCE(storefront.storefront_sort_order, 0)'))
            ->orderBy('products.name')
            ->limit($limit)
            ->get($this->productSelectColumns());

        if ($rows->isEmpty()) {
            return $this->publishedProducts($limit);
        }

        return $this->formatProductCollection($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function homepageProducts(int $limit = 6): array
    {
        $rows = $this->baseProductQuery()
            ->where('storefront.show_on_homepage', true)
            ->orderByDesc(DB::raw('COALESCE(storefront.storefront_sort_order, 0)'))
            ->orderBy('products.name')
            ->limit($limit)
            ->get($this->productSelectColumns());

        if ($rows->isEmpty()) {
            return array_slice($this->publishedProducts($limit), 0, $limit);
        }

        return $this->formatProductCollection($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchProducts(string $search, int $limit = 24): array
    {
        $rows = $this->baseProductQuery()
            ->where(function ($query) use ($search) {
                $query->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            })
            ->orderBy('products.name')
            ->limit($limit)
            ->get($this->productSelectColumns());

        return $this->formatProductCollection($rows);
    }

    /**
     * @return list<array{id: int, name: string, slug: string, image_url: string|null, product_count: int}>
     */
    public function activeCategories(int $limit = 40): array
    {
        if (! Schema::hasTable('categories')) {
            return [];
        }

        $rows = DB::table('categories')
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'image_path']);

        return $rows
            ->map(fn ($row) => $this->formatStorefrontCategory($row))
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, slug: string, image_url: string|null, product_count: int}>
     */
    public function childCategories(int $parentId): array
    {
        if (! Schema::hasTable('categories')) {
            return [];
        }

        return DB::table('categories')
            ->where('is_active', true)
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'image_path'])
            ->map(fn ($row) => $this->formatStorefrontCategory($row))
            ->all();
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     image_url: string|null,
     *     parent_id: int|null,
     *     parent_name: string|null,
     *     parent_slug: string|null,
     *     root_slug: string
     * }|null
     */
    public function findActiveCategoryBySlug(string $slug): ?array
    {
        if (! Schema::hasTable('categories')) {
            return null;
        }

        $row = DB::table('categories')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first(['id', 'name', 'slug', 'parent_id', 'image_path']);

        if ($row === null) {
            return null;
        }

        $parent = $row->parent_id
            ? DB::table('categories')->where('id', $row->parent_id)->first(['name', 'slug'])
            : null;

        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
            'image_url' => $this->publicStorageUrl($row->image_path ?? null),
            'parent_id' => $row->parent_id ? (int) $row->parent_id : null,
            'parent_name' => $parent?->name,
            'parent_slug' => $parent?->slug,
            'root_slug' => $this->rootSlug((int) $row->id, $row->slug),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function productsByCategoryId(int $categoryId, int $limit = 48): array
    {
        return $this->productsByCategoryIds($this->descendantCategoryIds($categoryId), $limit);
    }

    /**
     * @param  list<int>  $categoryIds
     * @return list<array<string, mixed>>
     */
    public function productsByCategoryIds(array $categoryIds, int $limit = 48): array
    {
        $categoryIds = array_values(array_unique($categoryIds));

        if ($categoryIds === []) {
            return [];
        }

        $rows = $this->baseProductQuery()
            ->where(function ($query) use ($categoryIds) {
                $query->whereIn('products.primary_category_id', $categoryIds)
                    ->orWhereExists(function ($sub) use ($categoryIds) {
                        $sub->select(DB::raw(1))
                            ->from('category_product')
                            ->whereColumn('category_product.product_id', 'products.id')
                            ->whereIn('category_product.category_id', $categoryIds);
                    });
            })
            ->orderByDesc(DB::raw('COALESCE(storefront.storefront_sort_order, 0)'))
            ->orderBy('products.name')
            ->limit($limit)
            ->get($this->productSelectColumns());

        return $this->formatProductCollection($rows);
    }

    /**
     * Active stories grouped by start date (one player per date).
     *
     * @return list<array{key: string, label: string, cover: array<string, mixed>, count: int, stories: list<array<string, mixed>>}>
     */
    public function activeStoryGroups(int $limit = 40): array
    {
        if (! app(ModuleManager::class)->enabled('marketing') || ! Schema::hasTable('stories')) {
            return [];
        }

        $now = now();

        $rows = DB::table('stories')
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->orderByDesc(DB::raw('COALESCE(starts_at, created_at)'))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
                'id',
                'title',
                'type',
                'media_path',
                'action_url',
                'action_label',
                'starts_at',
                'created_at',
            ]);

        $groups = [];

        foreach ($rows as $row) {
            $anchor = $row->starts_at ?: $row->created_at;
            $key = Carbon::parse($anchor)->toDateString();
            $story = [
                'id' => (int) $row->id,
                'title' => $row->title ?: 'Story',
                'type' => $row->type === 'video' ? 'video' : 'image',
                'media_url' => '/storage/'.ltrim(str_replace('\\', '/', $row->media_path), '/'),
                'action_url' => $row->action_url,
                'action_label' => $row->action_label ?: 'Order Now',
                'starts_on' => $key,
            ];

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'label' => $story['title'],
                    'cover' => $story,
                    'count' => 0,
                    'stories' => [],
                ];
            }

            $groups[$key]['stories'][] = $story;
            $groups[$key]['count']++;
        }

        return array_values($groups);
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
        $reviewMap = $this->reviewStatsMap([$productId]);
        $reviewStats = $reviewMap[$productId] ?? ['avg' => null, 'count' => 0];
        $related = collect($this->publishedProducts(8))
            ->reject(fn (array $row) => $row['id'] === $productId)
            ->take(4)
            ->values()
            ->all();

        return [
            'id' => $productId,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'type' => $product->type,
            'description' => $product->description,
            'is_featured' => (bool) $product->is_featured,
            'has_variants' => $product->type === 'variant' || count($formattedVariants) > 0,
            'media' => $productMedia,
            'image_url' => $primaryProductImage['url'] ?? null,
            'variants' => $formattedVariants,
            'price' => $resolved['resolved'] ? $resolved['price'] : null,
            'currency' => $resolved['currency'] ?? 'BDT',
            'price_message' => $resolved['resolved'] ? null : ($resolved['message'] ?? 'Price unavailable'),
            'stock_available' => $available['available'],
            'in_stock' => (float) $available['available'] > 0,
            'rating_avg' => $reviewStats['avg'],
            'rating_count' => $reviewStats['count'],
            'reviews' => $this->approvedReviewsForProduct($productId),
            'related' => $related,
        ];
    }

    /**
     * @return array{id: int, name: string, slug: string, image_url: string|null, product_count: int}
     */
    private function formatStorefrontCategory(object $row): array
    {
        $ids = $this->descendantCategoryIds((int) $row->id);

        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
            'image_url' => $this->publicStorageUrl($row->image_path ?? null),
            'product_count' => $this->publishedProductCountForCategories($ids),
        ];
    }

    /**
     * @return list<int>
     */
    private function descendantCategoryIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $queue = [$categoryId];
        $childrenByParent = $this->activeChildrenByParent();

        while ($queue !== []) {
            $current = array_shift($queue);

            foreach ($childrenByParent[$current] ?? [] as $childId) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, list<int>>
     */
    private function activeChildrenByParent(): array
    {
        if ($this->activeChildrenByParent !== null) {
            return $this->activeChildrenByParent;
        }

        $this->activeChildrenByParent = [];

        if (! Schema::hasTable('categories')) {
            return $this->activeChildrenByParent;
        }

        foreach (DB::table('categories')->where('is_active', true)->get(['id', 'parent_id']) as $row) {
            if ($row->parent_id === null) {
                continue;
            }

            $parentId = (int) $row->parent_id;
            $this->activeChildrenByParent[$parentId][] = (int) $row->id;
        }

        return $this->activeChildrenByParent;
    }

    /**
     * @param  list<int>  $categoryIds
     */
    private function publishedProductCountForCategories(array $categoryIds): int
    {
        if ($categoryIds === [] || ! Schema::hasTable('products')) {
            return 0;
        }

        return (int) DB::table('products')
            ->where('publication_status', 'published')
            ->where('status', '!=', 'archived')
            ->where(function ($query) use ($categoryIds) {
                $query->whereIn('primary_category_id', $categoryIds)
                    ->orWhereExists(function ($sub) use ($categoryIds) {
                        $sub->select(DB::raw(1))
                            ->from('category_product')
                            ->whereColumn('category_product.product_id', 'products.id')
                            ->whereIn('category_product.category_id', $categoryIds);
                    });
            })
            ->count();
    }

    private function rootSlug(int $categoryId, string $fallbackSlug): string
    {
        $current = DB::table('categories')->where('id', $categoryId)->first(['id', 'parent_id', 'slug']);

        while ($current && $current->parent_id) {
            $current = DB::table('categories')->where('id', $current->parent_id)->first(['id', 'parent_id', 'slug']);
        }

        return $current->slug ?? $fallbackSlug;
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }

    private function baseProductQuery()
    {
        return DB::table('products')
            ->leftJoin('product_storefront_settings as storefront', 'products.id', '=', 'storefront.product_id')
            ->where('products.publication_status', 'published')
            ->where('products.status', '!=', 'archived');
    }

    /**
     * @return list<string|Expression>
     */
    private function productSelectColumns(): array
    {
        return [
            'products.id',
            'products.name',
            'products.slug',
            'products.sku',
            'products.type',
            DB::raw('COALESCE(storefront.is_featured, 0) as is_featured'),
            DB::raw('COALESCE(storefront.show_on_homepage, 0) as show_on_homepage'),
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    private function formatProductCollection(Collection $rows): array
    {
        $ids = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $imageMap = $this->primaryImageMap($ids);
        $reviewMap = $this->reviewStatsMap($ids);

        return $rows->map(function ($row) use ($imageMap, $reviewMap) {
            $resolved = $this->prices->resolve(productId: (int) $row->id, quantity: 1);
            $available = $this->stock->available((int) $row->id);
            $reviews = $reviewMap[(int) $row->id] ?? ['avg' => null, 'count' => 0];

            return [
                'id' => (int) $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'sku' => $row->sku,
                'type' => $row->type,
                'is_featured' => (bool) $row->is_featured,
                'show_on_homepage' => (bool) ($row->show_on_homepage ?? false),
                'image_url' => $imageMap[(int) $row->id] ?? null,
                'price' => $resolved['resolved'] ? $resolved['price'] : null,
                'currency' => $resolved['currency'] ?? 'BDT',
                'price_message' => $resolved['resolved'] ? null : ($resolved['message'] ?? 'Price unavailable'),
                'stock_available' => $available['available'],
                'in_stock' => (float) $available['available'] > 0,
                'rating_avg' => $reviews['avg'],
                'rating_count' => $reviews['count'],
                'has_variants' => $row->type === 'variant',
            ];
        })->all();
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

    /**
     * @param  list<int>  $productIds
     * @return array<int, array{avg: float|null, count: int}>
     */
    private function reviewStatsMap(array $productIds): array
    {
        if ($productIds === [] || ! Schema::hasTable('product_reviews')) {
            return [];
        }

        return DB::table('product_reviews')
            ->whereIn('product_id', $productIds)
            ->where('status', 'approved')
            ->groupBy('product_id')
            ->get([
                'product_id',
                DB::raw('AVG(rating) as rating_avg'),
                DB::raw('COUNT(*) as rating_count'),
            ])
            ->mapWithKeys(fn ($row) => [
                (int) $row->product_id => [
                    'avg' => round((float) $row->rating_avg, 1),
                    'count' => (int) $row->rating_count,
                ],
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, author_name: string, rating: int, title: string|null, body: string, created_at: string|null}>
     */
    private function approvedReviewsForProduct(int $productId, int $limit = 12): array
    {
        if (! Schema::hasTable('product_reviews')) {
            return [];
        }

        return DB::table('product_reviews')
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->orderByDesc('approved_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get([
                'id',
                'author_name',
                'rating',
                'title',
                'body',
                'created_at',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'author_name' => $row->author_name,
                'rating' => (int) $row->rating,
                'title' => $row->title,
                'body' => $row->body,
                'created_at' => $row->created_at
                    ? Carbon::parse($row->created_at)->toIso8601String()
                    : null,
            ])
            ->all();
    }
}
