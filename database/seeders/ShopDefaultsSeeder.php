<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Commerce\Models\PriceList;
use Modules\Inventory\Models\Warehouse;

/**
 * Ensures progressive-complexity defaults exist even when full Commerce/Inventory seeders are skipped.
 */
class ShopDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        if (Schema::hasTable('price_lists')) {
            PriceList::query()->firstOrCreate(
                ['code' => 'retail'],
                [
                    'name' => 'Retail',
                    'description' => 'Standard retail pricing',
                    'currency' => 'BDT',
                    'is_active' => true,
                    'is_default' => true,
                    'sort_order' => 1,
                ],
            );

            if (! PriceList::query()->where('is_default', true)->exists()) {
                PriceList::query()->where('code', 'retail')->update(['is_default' => true]);
            }
        }

        if (Schema::hasTable('warehouses')) {
            Warehouse::query()->firstOrCreate(
                ['code' => 'MAIN'],
                [
                    'name' => 'Main Warehouse',
                    'address' => 'Primary stock location',
                    'is_active' => true,
                    'is_default' => true,
                    'sort_order' => 1,
                ],
            );

            if (! Warehouse::query()->where('is_default', true)->exists()) {
                Warehouse::query()->where('code', 'MAIN')->update(['is_default' => true]);
            }
        }
    }
}
