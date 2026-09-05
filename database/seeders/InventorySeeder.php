<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\StockMovementType;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\StockService;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $main = Warehouse::query()->firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Warehouse',
                'address' => 'Primary stock location',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
        );

        Warehouse::query()->firstOrCreate(
            ['code' => 'SHOWROOM'],
            [
                'name' => 'Showroom',
                'address' => 'Retail floor stock',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],
        );

        $products = DB::table('products')
            ->where('status', '!=', 'archived')
            ->where('type', 'simple')
            ->orderBy('id')
            ->limit(20)
            ->get(['id']);

        if ($products->isEmpty()) {
            return;
        }

        $stock = app(StockService::class);

        foreach ($products as $index => $product) {
            $exists = DB::table('stock_levels')
                ->where('warehouse_id', $main->id)
                ->where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->exists();

            if ($exists) {
                continue;
            }

            $stock->move(
                warehouseId: $main->id,
                productId: $product->id,
                productVariantId: null,
                type: StockMovementType::AdjustmentIn,
                quantity: 50 + ($index * 10),
                note: 'Opening stock (seeder)',
                reorderPoint: 10,
            );
        }
    }
}
