<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Ecommerce\Models\ProductStorefrontSetting;

class OnlineProductService extends Service
{
    /**
     * @return array{
     *     published: int,
     *     not_published: int,
     *     unpublished: int,
     *     active_not_published: int,
     *     featured: int,
     *     homepage: int
     * }
     */
    public function publicationStats(): array
    {
        return [
            'published' => DB::table('products')->where('publication_status', PublicationStatus::Published->value)->count(),
            'not_published' => DB::table('products')->where('publication_status', PublicationStatus::NotPublished->value)->count(),
            'unpublished' => DB::table('products')->where('publication_status', PublicationStatus::Unpublished->value)->count(),
            'active_not_published' => DB::table('products')
                ->where('status', 'active')
                ->where('publication_status', PublicationStatus::NotPublished->value)
                ->count(),
            'featured' => ProductStorefrontSetting::query()->where('is_featured', true)->count(),
            'homepage' => ProductStorefrontSetting::query()->where('show_on_homepage', true)->count(),
        ];
    }

    public function listPaginated(
        ?string $search = null,
        ?PublicationStatus $publicationStatus = null,
        ?string $lifecycleStatus = null,
        ?bool $featuredOnly = null,
        ?bool $homepageOnly = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $query = DB::table('products')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('product_storefront_settings as storefront', 'products.id', '=', 'storefront.product_id')
            ->where('products.status', '!=', 'archived')
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.type',
                'products.status',
                'products.publication_status',
                'products.created_at',
                'brands.name as brand_name',
                DB::raw('COALESCE(storefront.is_featured, 0) as is_featured'),
                DB::raw('COALESCE(storefront.show_on_homepage, 0) as show_on_homepage'),
                DB::raw('COALESCE(storefront.storefront_sort_order, 0) as storefront_sort_order'),
            ])
            ->orderByDesc('storefront_sort_order')
            ->orderBy('products.name');

        if ($publicationStatus) {
            $query->where('products.publication_status', $publicationStatus->value);
        }

        if ($lifecycleStatus) {
            $query->where('products.status', $lifecycleStatus);
        }

        if ($featuredOnly) {
            $query->where('storefront.is_featured', true);
        }

        if ($homepageOnly) {
            $query->where('storefront.show_on_homepage', true);
        }

        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString()->through(function ($row) {
            $pub = PublicationStatus::tryFrom($row->publication_status);

            return [
                'id' => $row->id,
                'name' => $row->name,
                'sku' => $row->sku,
                'type' => $row->type,
                'status' => $row->status,
                'publication_status' => $row->publication_status,
                'publication_status_label' => $pub?->label(),
                'brand' => $row->brand_name,
                'can_publish' => $pub?->canTransitionTo(PublicationStatus::Published) ?? false,
                'can_unpublish' => $pub?->canTransitionTo(PublicationStatus::Unpublished) ?? false,
                'is_featured' => (bool) $row->is_featured,
                'show_on_homepage' => (bool) $row->show_on_homepage,
                'storefront_sort_order' => (int) $row->storefront_sort_order,
                'created_at' => $row->created_at,
            ];
        });
    }

    /**
     * @param  array{is_featured?: bool, show_on_homepage?: bool, storefront_sort_order?: int}  $data
     */
    public function updatePresentation(int $productId, array $data): void
    {
        $this->assertProductEditable($productId);

        $settings = ProductStorefrontSetting::query()->firstOrCreate(
            ['product_id' => $productId],
            [
                'is_featured' => false,
                'show_on_homepage' => false,
                'storefront_sort_order' => 0,
            ],
        );

        if (array_key_exists('is_featured', $data)) {
            $settings->is_featured = $data['is_featured'];
        }

        if (array_key_exists('show_on_homepage', $data)) {
            $settings->show_on_homepage = $data['show_on_homepage'];
        }

        if (array_key_exists('storefront_sort_order', $data)) {
            $settings->storefront_sort_order = $data['storefront_sort_order'];
        }

        $settings->save();
    }

    /**
     * @param  list<int>  $productIds
     * @param  array{is_featured?: bool, show_on_homepage?: bool}  $data
     */
    public function bulkUpdatePresentation(array $productIds, array $data): int
    {
        $updated = 0;

        foreach ($productIds as $productId) {
            try {
                $this->updatePresentation((int) $productId, $data);
                $updated++;
            } catch (ValidationException) {
                continue;
            }
        }

        return $updated;
    }

    public function transitionPublication(int $productId, PublicationStatus $to): void
    {
        $product = DB::table('products')->where('id', $productId)->first();

        if ($product === null) {
            throw ValidationException::withMessages(['product' => 'Product not found.']);
        }

        if ($product->status === 'archived') {
            throw ValidationException::withMessages(['product' => 'Archived products cannot change publication status.']);
        }

        $from = PublicationStatus::tryFrom($product->publication_status ?? '');

        if ($from === null || ! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'publication_status' => 'Invalid publication transition.',
            ]);
        }

        if ($to === PublicationStatus::Published && ! in_array($product->status, ['active', 'approved'], true)) {
            throw ValidationException::withMessages([
                'publication_status' => 'Only approved or active products can be published to the storefront.',
            ]);
        }

        DB::table('products')->where('id', $productId)->update([
            'publication_status' => $to->value,
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<int>  $productIds
     */
    public function bulkTransition(array $productIds, PublicationStatus $to): int
    {
        $updated = 0;

        foreach ($productIds as $productId) {
            try {
                $this->transitionPublication((int) $productId, $to);
                $updated++;
            } catch (ValidationException) {
                continue;
            }
        }

        return $updated;
    }

    private function assertProductEditable(int $productId): void
    {
        $product = DB::table('products')->where('id', $productId)->first();

        if ($product === null) {
            throw ValidationException::withMessages(['product' => 'Product not found.']);
        }

        if ($product->status === 'archived') {
            throw ValidationException::withMessages(['product' => 'Archived products cannot change storefront presentation.']);
        }
    }
}
