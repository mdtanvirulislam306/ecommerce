<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\Product;
use Modules\Commerce\Models\Courier;
use Modules\Commerce\Models\CustomerGroup;
use Modules\Commerce\Models\PriceList;
use Modules\Commerce\Models\PriceListItem;
use Modules\Commerce\Services\PriceHistoryService;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        $retail = PriceList::query()->firstOrCreate(
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

        $wholesale = PriceList::query()->firstOrCreate(
            ['code' => 'wholesale'],
            [
                'name' => 'Wholesale',
                'description' => 'Wholesale / bulk pricing',
                'currency' => 'BDT',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],
        );

        $dealer = PriceList::query()->firstOrCreate(
            ['code' => 'dealer'],
            [
                'name' => 'Dealer',
                'description' => 'Dealer / distributor pricing',
                'currency' => 'BDT',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
            ],
        );

        CustomerGroup::query()->firstOrCreate(
            ['code' => 'retail'],
            [
                'name' => 'Retail Customers',
                'price_list_id' => $retail->id,
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        CustomerGroup::query()->firstOrCreate(
            ['code' => 'wholesale'],
            [
                'name' => 'Wholesale Buyers',
                'price_list_id' => $wholesale->id,
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        CustomerGroup::query()->firstOrCreate(
            ['code' => 'dealer'],
            [
                'name' => 'Dealers',
                'price_list_id' => $dealer->id,
                'is_active' => true,
                'sort_order' => 3,
            ],
        );

        Courier::query()->firstOrCreate(
            ['code' => 'PATHAO'],
            [
                'name' => 'Pathao',
                'tracking_url_template' => 'https://merchant.pathao.com/tracking/{tracking}',
                'is_active' => true,
            ],
        );

        $products = Product::query()->orderBy('id')->limit(10)->get();

        foreach ($products as $product) {
            $base = 500 + ($product->id * 50);

            $this->seedTiers($retail, $product->id, [
                1 => $base,
                10 => $base - 50,
                50 => $base - 100,
            ]);

            $this->seedTiers($wholesale, $product->id, [
                1 => $base - 80,
                25 => $base - 120,
                100 => $base - 180,
            ]);

            $this->seedTiers($dealer, $product->id, [
                1 => $base - 120,
                50 => $base - 200,
            ]);
        }

        app(PriceHistoryService::class)->backfillFromExistingItems();
    }

    /**
     * @param  array<int, int|float>  $tiers
     */
    private function seedTiers(PriceList $priceList, int $productId, array $tiers): void
    {
        foreach ($tiers as $minQuantity => $price) {
            PriceListItem::query()->firstOrCreate(
                [
                    'price_list_id' => $priceList->id,
                    'product_id' => $productId,
                    'product_variant_id' => null,
                    'min_quantity' => $minQuantity,
                ],
                ['price' => $price],
            );
        }
    }
}
