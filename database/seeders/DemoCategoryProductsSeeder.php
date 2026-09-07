<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Catalog\Enums\MediaType;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use Modules\Catalog\Models\Unit;
use Modules\Catalog\Services\ProductService;

class DemoCategoryProductsSeeder extends Seeder
{
    /**
     * Demo catalog lines keyed by category slug (2–3 products each).
     *
     * @var array<string, list<array{name: string, price: float, stock: int, description: string}>>
     */
    private array $catalog = [
        'fruits-vegetables' => [
            ['name' => 'Fresh Banana (Dozen)', 'price' => 120, 'stock' => 80, 'description' => 'Demo ripe bananas for everyday fruit bowls.'],
            ['name' => 'Tomato 1kg', 'price' => 65, 'stock' => 100, 'description' => 'Demo local tomatoes for cooking and salad.'],
            ['name' => 'Potato 1kg', 'price' => 45, 'stock' => 120, 'description' => 'Demo potatoes for fries, curry, and mash.'],
        ],
        'meat-fish' => [
            ['name' => 'Chicken Broiler 1kg', 'price' => 280, 'stock' => 40, 'description' => 'Demo fresh chicken for home cooking.'],
            ['name' => 'Rui Fish Steak 500g', 'price' => 320, 'stock' => 35, 'description' => 'Demo cleaned fish steaks, ready to cook.'],
            ['name' => 'Beef Boneless 500g', 'price' => 450, 'stock' => 25, 'description' => 'Demo boneless beef for curry or steak.'],
        ],
        'cooking' => [
            ['name' => 'Soybean Oil 2L', 'price' => 390, 'stock' => 60, 'description' => 'Demo refined cooking oil for daily use.'],
            ['name' => 'Miniket Rice 5kg', 'price' => 520, 'stock' => 70, 'description' => 'Demo premium rice for family meals.'],
            ['name' => 'Turmeric Powder 200g', 'price' => 85, 'stock' => 90, 'description' => 'Demo kitchen spice staple.'],
        ],
        'sauces-pickles' => [
            ['name' => 'Tomato Ketchup 340g', 'price' => 140, 'stock' => 55, 'description' => 'Demo ketchup for snacks and meals.'],
            ['name' => 'Mixed Pickle 400g', 'price' => 180, 'stock' => 40, 'description' => 'Demo spicy mixed pickle jar.'],
            ['name' => 'Soy Sauce 150ml', 'price' => 95, 'stock' => 50, 'description' => 'Demo soy sauce for stir-fry and dips.'],
        ],
        'dairy-eggs' => [
            ['name' => 'Full Cream Milk 1L', 'price' => 95, 'stock' => 75, 'description' => 'Demo fresh milk carton.'],
            ['name' => 'Farm Eggs (12 pcs)', 'price' => 160, 'stock' => 65, 'description' => 'Demo farm eggs pack.'],
            ['name' => 'Cheddar Cheese Slice 200g', 'price' => 220, 'stock' => 30, 'description' => 'Demo cheese slices for sandwiches.'],
        ],
        'breakfast' => [
            ['name' => 'Corn Flakes 375g', 'price' => 310, 'stock' => 45, 'description' => 'Demo breakfast cereal box.'],
            ['name' => 'Instant Oats 500g', 'price' => 240, 'stock' => 50, 'description' => 'Demo oats for porridge and smoothies.'],
            ['name' => 'Honey 250g', 'price' => 280, 'stock' => 35, 'description' => 'Demo pure honey jar.'],
        ],
        'candy-chocolate' => [
            ['name' => 'Milk Chocolate Bar 40g', 'price' => 60, 'stock' => 100, 'description' => 'Demo chocolate bar for sweet cravings.'],
            ['name' => 'Assorted Candy Pack', 'price' => 90, 'stock' => 80, 'description' => 'Demo mixed candy pouch.'],
            ['name' => 'Dark Chocolate 70% 80g', 'price' => 150, 'stock' => 40, 'description' => 'Demo dark chocolate tablet.'],
        ],
        'snacks' => [
            ['name' => 'Potato Chips Classic 40g', 'price' => 35, 'stock' => 120, 'description' => 'Demo salted chips pack.'],
            ['name' => 'Chanachur Mix 200g', 'price' => 70, 'stock' => 90, 'description' => 'Demo spicy snack mix.'],
            ['name' => 'Cream Biscuits 200g', 'price' => 55, 'stock' => 85, 'description' => 'Demo biscuit pack for tea time.'],
        ],
        'beverages' => [
            ['name' => 'Mineral Water 500ml', 'price' => 20, 'stock' => 200, 'description' => 'Demo bottled drinking water.'],
            ['name' => 'Orange Juice 1L', 'price' => 180, 'stock' => 45, 'description' => 'Demo fruit juice carton.'],
            ['name' => 'Cola Soft Drink 250ml', 'price' => 35, 'stock' => 110, 'description' => 'Demo chilled soft drink can.'],
        ],
        'baking' => [
            ['name' => 'Baking Powder 100g', 'price' => 55, 'stock' => 60, 'description' => 'Demo baking powder tin.'],
            ['name' => 'Cocoa Powder 200g', 'price' => 190, 'stock' => 40, 'description' => 'Demo cocoa for cakes and drinks.'],
            ['name' => 'Vanilla Essence 30ml', 'price' => 75, 'stock' => 50, 'description' => 'Demo baking essence bottle.'],
        ],
        'flour' => [
            ['name' => 'All Purpose Flour 1kg', 'price' => 80, 'stock' => 70, 'description' => 'Demo plain flour for roti and baking.'],
            ['name' => 'Whole Wheat Flour 1kg', 'price' => 90, 'stock' => 65, 'description' => 'Demo atta flour pack.'],
            ['name' => 'Cake Flour 500g', 'price' => 110, 'stock' => 40, 'description' => 'Demo fine flour for soft cakes.'],
        ],
        'frozen-canned' => [
            ['name' => 'Frozen Mixed Vegetables 400g', 'price' => 160, 'stock' => 35, 'description' => 'Demo frozen veggie pack.'],
            ['name' => 'Canned Chickpeas 400g', 'price' => 95, 'stock' => 55, 'description' => 'Demo ready chickpeas can.'],
            ['name' => 'Frozen Paratha 10 pcs', 'price' => 210, 'stock' => 40, 'description' => 'Demo frozen flatbread pack.'],
        ],
        'frozen-canned-01' => [
            ['name' => 'Canned Tuna 180g', 'price' => 220, 'stock' => 30, 'description' => 'Demo canned tuna in oil.'],
            ['name' => 'Frozen French Fries 500g', 'price' => 180, 'stock' => 35, 'description' => 'Demo oven-ready fries.'],
        ],
        'nuts-dried-fruits' => [
            ['name' => 'Cashew Nuts 200g', 'price' => 420, 'stock' => 25, 'description' => 'Demo roasted cashew pack.'],
            ['name' => 'Almonds 200g', 'price' => 390, 'stock' => 25, 'description' => 'Demo whole almonds.'],
            ['name' => 'Raisins 250g', 'price' => 150, 'stock' => 40, 'description' => 'Demo seedless raisins.'],
        ],
        'watch' => [
            ['name' => 'Classic Analog Watch', 'price' => 2490, 'stock' => 15, 'description' => 'Demo everyday analog wristwatch.'],
            ['name' => 'Sport Digital Watch', 'price' => 1890, 'stock' => 18, 'description' => 'Demo digital sports watch.'],
            ['name' => 'Minimal Leather Watch', 'price' => 3290, 'stock' => 12, 'description' => 'Demo leather-strap dress watch.'],
        ],
        'men-clothing' => [
            ['name' => 'Casual Polo Shirt', 'price' => 890, 'stock' => 30, 'description' => 'Demo cotton polo for casual wear.'],
            ['name' => 'Slim Fit Jeans', 'price' => 1490, 'stock' => 22, 'description' => 'Demo denim jeans.'],
            ['name' => 'Cotton Hoodie', 'price' => 1690, 'stock' => 20, 'description' => 'Demo soft hoodie for cooler days.'],
        ],
    ];

    public function run(): void
    {
        $unitId = Unit::query()->where('code', 'pcs')->value('id')
            ?? Unit::query()->value('id');
        $brandId = Brand::query()->where('slug', 'generic')->value('id')
            ?? Brand::query()->value('id');

        $products = app(ProductService::class);
        $created = 0;
        $skipped = 0;

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        foreach ($categories as $category) {
            $items = $this->catalog[$category->slug]
                ?? $this->fallbackItems($category->name);

            foreach ($items as $index => $item) {
                $sku = 'DEMO-'.Str::upper(Str::slug($category->slug, '')).'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

                if (Product::query()->where('sku', $sku)->exists()) {
                    $skipped++;

                    continue;
                }

                $product = $products->create([
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'sku' => $sku,
                    'type' => 'simple',
                    'status' => ProductStatus::Active->value,
                    'publication_status' => PublicationStatus::Published->value,
                    'primary_category_id' => $category->id,
                    'category_ids' => array_values(array_filter([
                        $category->id,
                        $category->parent_id,
                    ])),
                    'brand_id' => $brandId,
                    'unit_id' => $unitId,
                    'selling_price' => $item['price'],
                    'opening_stock' => $item['stock'],
                    'sort_order' => $index,
                ]);

                $this->attachCategoryImage($product, $category);
                $this->markStorefront($product->id, $index);
                $created++;
            }
        }

        $this->promoteExistingDemoProducts();

        $this->command?->info("Demo products created: {$created}, skipped (already exist): {$skipped}.");
    }

    private function promoteExistingDemoProducts(): void
    {
        if (! Schema::hasTable('product_storefront_settings')) {
            return;
        }

        $demoIds = Product::query()
            ->where('sku', 'like', 'DEMO-%')
            ->where('publication_status', PublicationStatus::Published)
            ->orderBy('id')
            ->pluck('id');

        foreach ($demoIds->values() as $index => $productId) {
            $this->markStorefront((int) $productId, $index % 3);
        }
    }

    private function markStorefront(int $productId, int $index): void
    {
        if (! Schema::hasTable('product_storefront_settings')) {
            return;
        }

        $exists = DB::table('product_storefront_settings')->where('product_id', $productId)->exists();
        $payload = [
            'is_featured' => $index < 2,
            'show_on_homepage' => $index < 2,
            'storefront_sort_order' => 100 - $index,
            'updated_at' => now(),
        ];

        if ($exists) {
            DB::table('product_storefront_settings')->where('product_id', $productId)->update($payload);

            return;
        }

        DB::table('product_storefront_settings')->insert([
            'product_id' => $productId,
            ...$payload,
            'created_at' => now(),
        ]);
    }

    /**
     * @return list<array{name: string, price: float, stock: int, description: string}>
     */
    private function fallbackItems(string $categoryName): array
    {
        return [
            [
                'name' => "Demo {$categoryName} Item A",
                'price' => 199,
                'stock' => 40,
                'description' => "Demo product for {$categoryName}.",
            ],
            [
                'name' => "Demo {$categoryName} Item B",
                'price' => 299,
                'stock' => 35,
                'description' => "Another demo product for {$categoryName}.",
            ],
        ];
    }

    private function attachCategoryImage(Product $product, Category $category): void
    {
        if (blank($category->image_path) || ! Storage::disk('public')->exists($category->image_path)) {
            return;
        }

        if ($product->media()->exists()) {
            return;
        }

        $extension = pathinfo($category->image_path, PATHINFO_EXTENSION) ?: 'jpg';
        $dest = 'products/'.uniqid('demo_', true).'.'.$extension;
        Storage::disk('public')->put($dest, Storage::disk('public')->get($category->image_path));

        ProductMedia::query()->create([
            'product_id' => $product->id,
            'path' => $dest,
            'type' => MediaType::Image,
            'alt' => $product->name,
            'is_primary' => true,
            'sort_order' => 1,
        ]);
    }
}
