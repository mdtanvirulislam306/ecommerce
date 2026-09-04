<?php

namespace Modules\Ecommerce\Services;

use App\Core\Contracts\PriceResolver;
use App\Core\Contracts\StockAvailability;
use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;

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
                'products.sku',
                'products.type',
                DB::raw('COALESCE(storefront.is_featured, 0) as is_featured'),
            ]);

        return $rows->map(function ($row) {
            $resolved = $this->prices->resolve(productId: $row->id, quantity: 1);
            $available = $this->stock->available($row->id);

            return [
                'id' => $row->id,
                'name' => $row->name,
                'sku' => $row->sku,
                'type' => $row->type,
                'is_featured' => (bool) $row->is_featured,
                'price' => $resolved['resolved'] ? $resolved['price'] : null,
                'currency' => $resolved['currency'] ?? 'BDT',
                'price_message' => $resolved['resolved'] ? null : ($resolved['message'] ?? 'Price unavailable'),
                'stock_available' => $available['available'],
                'in_stock' => (float) $available['available'] > 0,
            ];
        })->all();
    }
}
