<?php

namespace Modules\Catalog\Services;

use App\Core\Contracts\StockAvailability;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Enums\AttributeType;
use Modules\Catalog\Enums\MediaType;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\ProductType;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Catalog\Models\Attribute;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Collection;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductAttributeValue;
use Modules\Catalog\Models\ProductFamily;
use Modules\Catalog\Models\ProductMedia;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Models\Unit;
use Modules\Catalog\Support\GeneratesProductIdentifiers;

class ProductService extends Service
{
    use GeneratesProductIdentifiers;

    public function __construct(
        private readonly CatalogSettingsService $catalogSettings,
    ) {}

    public function listPaginated(
        ?string $search = null,
        ?ProductStatus $status = null,
        ?ProductType $type = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Product::query()
            ->with([
                'brand:id,name',
                'primaryCategory:id,name',
                'media' => fn ($query) => $query->where('is_primary', true)->limit(1),
            ])
            ->withCount('variants')
            ->when($status, fn ($query, $status) => $query->status($status))
            ->when($type, fn ($query, $type) => $query->type($type))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('internal_code', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Product $product) => $this->formatForList($product));
    }

    /**
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'brands' => Brand::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']),
            'units' => Unit::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'families' => ProductFamily::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'collections' => Collection::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'variant_attributes' => Attribute::query()
                ->where('is_active', true)
                ->where('type', AttributeType::Variant)
                ->with(['options' => fn ($query) => $query->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get(['id', 'name', 'code', 'input_type']),
            'informational_attributes' => Attribute::query()
                ->where('is_active', true)
                ->where('type', AttributeType::Informational)
                ->with(['options' => fn ($query) => $query->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get(['id', 'name', 'code', 'input_type']),
            'product_types' => $this->enumOptions(ProductType::cases()),
            'product_statuses' => $this->enumOptions(ProductStatus::cases()),
            'publication_statuses' => $this->enumOptions(PublicationStatus::cases()),
            'catalog_defaults' => [
                'status' => $this->catalogSettings->shouldAutoSubmitForReview()
                    ? ProductStatus::PendingReview->value
                    : $this->catalogSettings->defaultProductStatus()->value,
                'publication_status' => $this->catalogSettings->defaultPublicationStatus()->value,
                'unit_id' => $this->catalogSettings->get()->default_unit_id,
                'sku_prefix' => $this->catalogSettings->get()->sku_prefix ?? '',
                'auto_submit_for_review' => $this->catalogSettings->shouldAutoSubmitForReview(),
            ],
            'catalog_requirements' => [
                'brand' => $this->catalogSettings->get()->require_brand_on_create,
                'primary_category' => $this->catalogSettings->get()->require_primary_category_on_create,
                'unit' => $this->catalogSettings->get()->require_unit_on_create,
            ],
        ];
    }

    public function findForDetail(Product $product): array
    {
        $product->load([
            'brand:id,name',
            'primaryCategory:id,name',
            'productFamily:id,name',
            'unit:id,name,code',
            'categories:id,name',
            'collections:id,name',
            'attributeValues.attribute:id,name,code,input_type',
            'attributeValues.option:id,value',
            'variants.attributeValues.attribute:id,name',
            'variants.attributeValues.option:id,value',
            'variants.media',
            'media',
        ]);

        return $this->formatForDetail($product);
    }

    /**
     * @param  list<UploadedFile>  $mediaFiles
     */
    public function create(array $data, array $mediaFiles = []): Product
    {
        return DB::transaction(function () use ($data, $mediaFiles) {
            if ($this->catalogSettings->shouldAutoSubmitForReview()
                && (empty($data['status']) || $data['status'] === ProductStatus::Draft->value)
            ) {
                $data['status'] = ProductStatus::PendingReview->value;
            }

            if (empty($data['unit_id']) && $this->catalogSettings->get()->default_unit_id) {
                $data['unit_id'] = $this->catalogSettings->get()->default_unit_id;
            }

            $product = Product::query()->create($this->productAttributes($data));

            $this->syncRelations($product, $data);

            if ($product->isVariant() && ! empty($data['variants'])) {
                $this->createVariants($product, $data['variants']);
            }

            if ($mediaFiles !== []) {
                $this->uploadMedia($product, $mediaFiles);
            }

            $this->attachLibraryMedia($product, $data['media_library_ids'] ?? []);

            $this->applySimpleCommerceInventory($product, $data);

            return $product->fresh();
        });
    }

    /**
     * @param  list<UploadedFile>  $mediaFiles
     */
    public function update(Product $product, array $data, array $mediaFiles = []): Product
    {
        return DB::transaction(function () use ($product, $data, $mediaFiles) {
            $product->update($this->productAttributes($data, $product));

            $this->syncRelations($product, $data);

            if ($product->isVariant()) {
                $this->syncVariants($product, $data['variants'] ?? []);
            } else {
                $product->variants()->delete();
            }

            if (! empty($data['remove_media_ids'])) {
                $this->removeMediaByIds($product, $data['remove_media_ids']);
            }

            if ($mediaFiles !== []) {
                $this->uploadMedia($product, $mediaFiles);
            }

            $this->attachLibraryMedia($product, $data['media_library_ids'] ?? []);

            if (! empty($data['primary_media_id'])) {
                $this->setPrimaryMedia($product, (int) $data['primary_media_id']);
            }

            $this->applySimpleCommerceInventory($product, $data, openingStockOnlyIfMissing: true);

            return $product->fresh();
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->load('media');

            foreach ($product->media as $media) {
                Storage::disk('public')->delete($media->path);
            }

            $product->delete();
        });
    }

    /**
     * @return array{pending: int, approved: int, draft: int}
     */
    public function approvalStats(): array
    {
        return [
            'pending' => Product::query()->status(ProductStatus::PendingReview)->count(),
            'approved' => Product::query()->status(ProductStatus::Approved)->count(),
            'draft' => Product::query()->status(ProductStatus::Draft)->count(),
        ];
    }

    public function transitionStatus(Product $product, ProductStatus $to): Product
    {
        $from = $product->status;

        if ($from === null || ! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition from {$from?->label()} to {$to->label()}.",
            ]);
        }

        $product->update(['status' => $to]);

        return $product->fresh();
    }

    /**
     * @param  list<int>  $productIds
     */
    public function bulkTransition(array $productIds, ProductStatus $to): int
    {
        return DB::transaction(function () use ($productIds, $to) {
            $updated = 0;

            foreach (Product::query()->whereIn('id', $productIds)->get() as $product) {
                if ($product->status?->canTransitionTo($to)) {
                    $product->update(['status' => $to]);
                    $updated++;
                }
            }

            return $updated;
        });
    }

    /**
     * @return list<array{action: string, label: string}>
     */
    public function approvalActionsFor(Product $product): array
    {
        $status = $product->status;

        if ($status === null) {
            return [];
        }

        $actions = [];

        if ($status->canTransitionTo(ProductStatus::PendingReview)) {
            $actions[] = ['action' => 'submit_review', 'label' => 'Submit for review'];
        }

        if ($status->canTransitionTo(ProductStatus::Approved)) {
            $actions[] = ['action' => 'approve', 'label' => 'Approve'];
        }

        if ($status->canTransitionTo(ProductStatus::Draft) && $status !== ProductStatus::Draft) {
            $actions[] = ['action' => 'reject', 'label' => 'Return to draft'];
        }

        if ($status->canTransitionTo(ProductStatus::Active)) {
            $actions[] = ['action' => 'activate', 'label' => 'Activate'];
        }

        if ($status->canTransitionTo(ProductStatus::Archived)) {
            $actions[] = ['action' => 'archive', 'label' => 'Archive'];
        }

        return $actions;
    }

    /**
     * @return array<string, mixed>
     */
    private function productAttributes(array $data, ?Product $product = null): array
    {
        $name = $data['name'];
        $type = $data['type'] ?? $product?->type ?? ProductType::Simple;
        $typeValue = $type instanceof ProductType ? $type : ProductType::from($type);
        $prefix = $this->catalogSettings->get()->sku_prefix;

        $slug = $this->uniquifySlug(
            filled($data['slug'] ?? null) ? (string) $data['slug'] : $name,
            Product::class,
            $product?->id,
        );

        $internalCode = $this->uniquifyInternalCode(
            filled($data['internal_code'] ?? null)
                ? (string) $data['internal_code']
                : ($product?->internal_code ?: Str::upper(Str::slug($name, '_')) ?: 'ITEM'),
            $product?->id,
        );

        $skuSeed = filled($data['sku'] ?? null)
            ? (string) $data['sku']
            : ($product?->sku ?: trim((string) $prefix).Str::upper(Str::slug($name, '') ?: 'SKU'));

        $sku = $this->uniquifySku($skuSeed, $product?->id);

        $barcode = filled($data['barcode'] ?? null)
            ? $this->uniquifyBarcode((string) $data['barcode'], $product?->id)
            : ($product?->barcode && ! filled($data['sku'] ?? null)
                ? $this->uniquifyBarcode((string) $product->barcode, $product?->id)
                : $this->barcodeFromSku($sku, $product?->id));

        $attributes = [
            'product_family_id' => $data['product_family_id'] ?? $product?->product_family_id,
            'brand_id' => $data['brand_id'] ?? $product?->brand_id,
            'primary_category_id' => $data['primary_category_id'] ?? $product?->primary_category_id,
            'unit_id' => $data['unit_id'] ?? $product?->unit_id,
            'type' => $typeValue,
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? $product?->description,
            'internal_code' => $internalCode,
            'sku' => $sku,
            'barcode' => $barcode,
            'status' => $data['status'] ?? $product?->status ?? $this->catalogSettings->defaultProductStatus(),
            'publication_status' => $data['publication_status'] ?? $product?->publication_status ?? $this->catalogSettings->defaultPublicationStatus(),
            'meta_title' => $data['meta_title'] ?? $product?->meta_title,
            'meta_description' => $data['meta_description'] ?? $product?->meta_description,
            'sort_order' => $data['sort_order'] ?? $product?->sort_order ?? 0,
        ];

        if ($typeValue === ProductType::Variant) {
            $attributes['sku'] = null;
            $attributes['barcode'] = null;
        }

        return $attributes;
    }

    private function syncRelations(Product $product, array $data): void
    {
        if (array_key_exists('category_ids', $data)) {
            $product->categories()->sync($data['category_ids'] ?? []);
        }

        if (array_key_exists('collection_ids', $data)) {
            $product->collections()->sync($data['collection_ids'] ?? []);
        }

        if (array_key_exists('informational_attributes', $data)) {
            $this->syncInformationalAttributes($product, $data['informational_attributes'] ?? []);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     */
    private function createVariants(Product $product, array $variants): void
    {
        $variantService = app(ProductVariantService::class);
        $prefix = $this->catalogSettings->get()->sku_prefix;

        foreach ($variants as $index => $variantData) {
            $variantData['sku'] = filled($variantData['sku'] ?? null)
                ? $this->uniquifySku((string) $variantData['sku'])
                : $this->uniqueVariantSku($product->name, $index + 1, $prefix);

            $variantData['barcode'] = filled($variantData['barcode'] ?? null)
                ? $this->uniquifyBarcode((string) $variantData['barcode'])
                : $this->barcodeFromSku($variantData['sku']);

            $variant = $variantService->create($product, array_merge($variantData, [
                'sort_order' => $variantData['sort_order'] ?? $index,
            ]));

            $this->attachLibraryMedia($product, $variantData['media_library_ids'] ?? [], $variant->id);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $incomingIds = collect($variants)->pluck('id')->filter()->all();

        $product->variants()
            ->whereNotIn('id', $incomingIds)
            ->each(fn (ProductVariant $variant) => app(ProductVariantService::class)->delete($variant));

        $variantService = app(ProductVariantService::class);

        $prefix = $this->catalogSettings->get()->sku_prefix;

        foreach ($variants as $index => $variantData) {
            if (! empty($variantData['id'])) {
                $variant = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->find($variantData['id']);

                if ($variant) {
                    $variantData['sku'] = filled($variantData['sku'] ?? null)
                        ? $this->uniquifySku((string) $variantData['sku'], $product->id, $variant->id)
                        : $variant->sku;

                    $variantData['barcode'] = filled($variantData['barcode'] ?? null)
                        ? $this->uniquifyBarcode((string) $variantData['barcode'], $product->id, $variant->id)
                        : ($variant->barcode ?: $this->barcodeFromSku($variantData['sku'], $product->id, $variant->id));

                    $variantService->update($variant, array_merge($variantData, [
                        'sort_order' => $variantData['sort_order'] ?? $index,
                    ]));

                    if (array_key_exists('media_library_ids', $variantData) && filled($variantData['media_library_ids'] ?? null)) {
                        $this->attachLibraryMedia($product, $variantData['media_library_ids'] ?? [], $variant->id);
                    }
                }
            } else {
                $variantData['sku'] = filled($variantData['sku'] ?? null)
                    ? $this->uniquifySku((string) $variantData['sku'], $product->id)
                    : $this->uniqueVariantSku($product->name, $index + 1, $prefix);

                $variantData['barcode'] = filled($variantData['barcode'] ?? null)
                    ? $this->uniquifyBarcode((string) $variantData['barcode'], $product->id)
                    : $this->barcodeFromSku($variantData['sku'], $product->id);

                $variant = $variantService->create($product, array_merge($variantData, [
                    'sort_order' => $variantData['sort_order'] ?? $index,
                ]));

                $this->attachLibraryMedia($product, $variantData['media_library_ids'] ?? [], $variant->id);
            }
        }
    }

    /**
     * @param  list<array{attribute_id: int, attribute_option_id?: int|null, value?: string|null}>  $attributes
     */
    private function syncInformationalAttributes(Product $product, array $attributes): void
    {
        $product->attributeValues()->delete();

        foreach ($attributes as $attribute) {
            if (empty($attribute['attribute_id'])) {
                continue;
            }

            ProductAttributeValue::query()->create([
                'product_id' => $product->id,
                'attribute_id' => $attribute['attribute_id'],
                'attribute_option_id' => $attribute['attribute_option_id'] ?? null,
                'value' => $attribute['value'] ?? null,
            ]);
        }
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function uploadMedia(Product $product, array $files): void
    {
        $sortOrder = (int) $product->media()->max('sort_order');
        $hasPrimary = $product->media()->where('is_primary', true)->exists();

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('products', 'public');
            $sortOrder++;

            ProductMedia::query()->create([
                'product_id' => $product->id,
                'path' => $path,
                'type' => MediaType::Image,
                'is_primary' => ! $hasPrimary,
                'sort_order' => $sortOrder,
            ]);

            $hasPrimary = true;
        }
    }

    /**
     * Attach media library items by copying into product media storage.
     *
     * @param  list<int|string>  $ids
     */
    private function attachLibraryMedia(Product $product, array $ids, ?int $variantId = null): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($variantId !== null) {
            $ids = array_slice($ids, 0, 1);
        }

        if ($ids === [] || ! Schema::hasTable('media_library_items')) {
            return;
        }

        if ($variantId !== null) {
            $existing = $product->media()->where('product_variant_id', $variantId)->get();
            foreach ($existing as $media) {
                Storage::disk('public')->delete($media->path);
                $media->delete();
            }
        }

        $sortOrder = (int) $product->media()->max('sort_order');
        $hasPrimary = $variantId
            ? $product->media()->where('product_variant_id', $variantId)->where('is_primary', true)->exists()
            : $product->media()->whereNull('product_variant_id')->where('is_primary', true)->exists();

        $items = DB::table('media_library_items')->whereIn('id', $ids)->get()->keyBy('id');

        foreach ($ids as $id) {
            $item = $items->get($id);
            if ($item === null) {
                continue;
            }

            $disk = $item->disk ?: 'public';
            $extension = pathinfo((string) $item->path, PATHINFO_EXTENSION) ?: 'jpg';
            $dest = 'products/'.uniqid('lib_', true).'.'.$extension;

            if (! Storage::disk($disk)->exists($item->path)) {
                continue;
            }

            Storage::disk('public')->put($dest, Storage::disk($disk)->get($item->path));
            $sortOrder++;

            ProductMedia::query()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
                'path' => $dest,
                'type' => MediaType::Image,
                'alt' => $item->name,
                'is_primary' => ! $hasPrimary,
                'sort_order' => $sortOrder,
            ]);

            $hasPrimary = true;
        }
    }

    /**
     * @param  list<int>  $ids
     */
    private function removeMediaByIds(Product $product, array $ids): void
    {
        $mediaItems = $product->media()->whereIn('id', $ids)->get();

        foreach ($mediaItems as $media) {
            Storage::disk('public')->delete($media->path);
            $media->delete();
        }
    }

    private function setPrimaryMedia(Product $product, int $mediaId): void
    {
        $product->media()->update(['is_primary' => false]);
        $product->media()->where('id', $mediaId)->update(['is_primary' => true]);
    }

    /**
     * @param  list<\BackedEnum>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function enumOptions(array $cases): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            $cases,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formatForList(Product $product): array
    {
        $primaryMedia = $product->media->first();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'type' => $product->type?->value,
            'type_label' => $product->type?->label(),
            'status' => $product->status?->value,
            'status_label' => $product->status?->label(),
            'publication_status' => $product->publication_status?->value,
            'publication_status_label' => $product->publication_status?->label(),
            'brand' => $product->brand?->name,
            'primary_category' => $product->primaryCategory?->name,
            'variants_count' => $product->variants_count ?? 0,
            'thumbnail_url' => $primaryMedia?->url(),
            'created_at' => $product->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatForDetail(Product $product): array
    {
        return [
            'id' => $product->id,
            'product_family_id' => $product->product_family_id,
            'brand_id' => $product->brand_id,
            'primary_category_id' => $product->primary_category_id,
            'unit_id' => $product->unit_id,
            'type' => $product->type?->value,
            'type_label' => $product->type?->label(),
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'internal_code' => $product->internal_code,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'status' => $product->status?->value,
            'status_label' => $product->status?->label(),
            'publication_status' => $product->publication_status?->value,
            'publication_status_label' => $product->publication_status?->label(),
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'sort_order' => $product->sort_order,
            'brand' => $product->brand ? ['id' => $product->brand->id, 'name' => $product->brand->name] : null,
            'primary_category' => $product->primaryCategory ? ['id' => $product->primaryCategory->id, 'name' => $product->primaryCategory->name] : null,
            'product_family' => $product->productFamily ? ['id' => $product->productFamily->id, 'name' => $product->productFamily->name] : null,
            'unit' => $product->unit ? ['id' => $product->unit->id, 'name' => $product->unit->name, 'code' => $product->unit->code] : null,
            'category_ids' => $product->categories->pluck('id')->all(),
            'collection_ids' => $product->collections->pluck('id')->all(),
            'collections' => $product->collections->map(fn ($collection) => [
                'id' => $collection->id,
                'name' => $collection->name,
            ])->values()->all(),
            'informational_attributes' => $product->attributeValues->map(fn (ProductAttributeValue $value) => [
                'attribute_id' => $value->attribute_id,
                'attribute_name' => $value->attribute?->name,
                'attribute_option_id' => $value->attribute_option_id,
                'option_value' => $value->option?->value,
                'value' => $value->value,
            ])->all(),
            'variants' => $product->variants->map(function ($variant) {
                $variantMedia = $variant->media
                    ->sortBy('sort_order')
                    ->values()
                    ->map(fn ($media) => [
                        'id' => $media->id,
                        'url' => $media->url(),
                        'alt' => $media->alt,
                        'is_primary' => $media->is_primary,
                        'sort_order' => $media->sort_order,
                    ])
                    ->all();

                $primary = collect($variantMedia)->firstWhere('is_primary', true) ?? ($variantMedia[0] ?? null);

                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'name' => $variant->name,
                    'weight' => $variant->weight,
                    'is_active' => $variant->is_active,
                    'attributes' => $variant->attributeValues->map(fn ($value) => [
                        'attribute_id' => $value->attribute_id,
                        'attribute_name' => $value->attribute?->name,
                        'attribute_option_id' => $value->attribute_option_id,
                        'option_value' => $value->option?->value,
                    ])->all(),
                    'media' => $variantMedia,
                    'image_url' => $primary['url'] ?? null,
                ];
            })->all(),
            'media' => $product->media->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->url(),
                'type' => $media->type?->value,
                'alt' => $media->alt,
                'is_primary' => $media->is_primary,
                'sort_order' => $media->sort_order,
                'product_variant_id' => $media->product_variant_id,
            ])->all(),
            'selling_price' => $this->resolveRetailPrice($product->id),
            'current_stock' => $this->resolveCurrentStock($product->id),
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
            'approval_actions' => $this->approvalActionsFor($product),
        ];
    }

    private function resolveRetailPrice(int $productId): ?string
    {
        if (! Schema::hasTable('price_lists') || ! Schema::hasTable('price_list_items')) {
            return null;
        }

        $priceListId = DB::table('price_lists')->where('code', 'retail')->value('id')
            ?? DB::table('price_lists')->where('is_default', true)->value('id');

        if ($priceListId === null) {
            return null;
        }

        $price = DB::table('price_list_items')
            ->where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->where('min_quantity', 1)
            ->value('price');

        return $price !== null ? (string) $price : null;
    }

    private function resolveCurrentStock(int $productId): ?string
    {
        if (! app()->bound(StockAvailability::class)) {
            return null;
        }

        try {
            return app(StockAvailability::class)->available($productId)['available'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Simple-shop fields: write selling price to Retail list and opening stock to Main warehouse.
     * Catalog never imports Commerce/Inventory services — only contracts + shared tables.
     *
     * @param  array<string, mixed>  $data
     */
    private function applySimpleCommerceInventory(Product $product, array $data, bool $openingStockOnlyIfMissing = false): void
    {
        if (! $product->isSimple()) {
            return;
        }

        if (array_key_exists('selling_price', $data) && $data['selling_price'] !== null && $data['selling_price'] !== '') {
            $this->upsertRetailPrice($product->id, (string) $data['selling_price']);
        }

        if (! array_key_exists('opening_stock', $data) || $data['opening_stock'] === null || $data['opening_stock'] === '') {
            return;
        }

        $qty = (float) $data['opening_stock'];

        if ($qty <= 0) {
            return;
        }

        if (! Schema::hasTable('warehouses') || ! app()->bound(StockAvailability::class)) {
            return;
        }

        $warehouseId = DB::table('warehouses')
            ->where('is_default', true)
            ->value('id')
            ?? DB::table('warehouses')->where('code', 'MAIN')->value('id');

        if ($warehouseId === null) {
            return;
        }

        if ($openingStockOnlyIfMissing && Schema::hasTable('stock_levels')) {
            $exists = DB::table('stock_levels')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->exists();

            if ($exists) {
                return;
            }
        }

        app(StockAvailability::class)->receive(
            productId: $product->id,
            quantity: $qty,
            productVariantId: null,
            warehouseId: (int) $warehouseId,
            referenceType: 'product_opening',
            referenceId: $product->id,
            userId: auth()->id(),
        );
    }

    private function upsertRetailPrice(int $productId, string $price): void
    {
        if (! Schema::hasTable('price_lists') || ! Schema::hasTable('price_list_items')) {
            return;
        }

        $priceListId = DB::table('price_lists')->where('code', 'retail')->value('id')
            ?? DB::table('price_lists')->where('is_default', true)->value('id');

        if ($priceListId === null) {
            return;
        }

        $existingId = DB::table('price_list_items')
            ->where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->where('min_quantity', 1)
            ->value('id');

        if ($existingId) {
            DB::table('price_list_items')->where('id', $existingId)->update([
                'price' => $price,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('price_list_items')->insert([
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'product_variant_id' => null,
            'price' => $price,
            'min_quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
