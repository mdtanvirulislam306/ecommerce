<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Enums\AttributeInputType;
use Modules\Catalog\Enums\AttributeType;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\ProductType;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Catalog\Models\Attribute;
use Modules\Catalog\Models\AttributeOption;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Collection;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductFamily;
use Modules\Catalog\Models\Unit;
use Modules\Catalog\Models\UnitConversion;
use Modules\Catalog\Services\CatalogSettingsService;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $piece = Unit::query()->firstOrCreate(
            ['code' => 'pcs'],
            ['name' => 'Piece', 'is_active' => true, 'sort_order' => 1],
        );

        Unit::query()->firstOrCreate(
            ['code' => 'kg'],
            ['name' => 'Kilogram', 'is_active' => true, 'sort_order' => 2],
        );

        Unit::query()->firstOrCreate(
            ['code' => 'ctn'],
            ['name' => 'Carton', 'is_active' => true, 'sort_order' => 3],
        );

        Brand::query()->firstOrCreate(
            ['slug' => 'nike'],
            ['name' => 'Nike', 'is_active' => true, 'sort_order' => 1],
        );

        Brand::query()->firstOrCreate(
            ['slug' => 'adidas'],
            ['name' => 'Adidas', 'is_active' => true, 'sort_order' => 2],
        );

        Brand::query()->firstOrCreate(
            ['slug' => 'generic'],
            ['name' => 'Generic', 'is_active' => true, 'sort_order' => 3],
        );

        $men = Category::query()->firstOrCreate(
            ['slug' => 'men'],
            ['name' => 'Men', 'is_active' => true, 'sort_order' => 1],
        );

        $clothing = Category::query()->firstOrCreate(
            ['slug' => 'men-clothing'],
            ['name' => 'Clothing', 'parent_id' => $men->id, 'is_active' => true, 'sort_order' => 1],
        );

        Category::query()->firstOrCreate(
            ['slug' => 'men-t-shirt'],
            ['name' => 'T-Shirt', 'parent_id' => $clothing->id, 'is_active' => true, 'sort_order' => 1],
        );

        Category::query()->firstOrCreate(
            ['slug' => 'women'],
            ['name' => 'Women', 'is_active' => true, 'sort_order' => 2],
        );

        $grocery = Category::query()->firstOrCreate(
            ['slug' => 'grocery'],
            ['name' => 'Grocery', 'is_active' => true, 'sort_order' => 3],
        );

        ProductFamily::query()->firstOrCreate(
            ['slug' => 't-shirt'],
            ['name' => 'T-Shirt', 'description' => 'Apparel family for t-shirts', 'is_active' => true],
        );

        Collection::query()->firstOrCreate(
            ['slug' => 'summer-collection'],
            ['name' => 'Summer Collection', 'is_active' => true, 'sort_order' => 1],
        );

        Collection::query()->firstOrCreate(
            ['slug' => 'new-arrivals'],
            ['name' => 'New Arrivals', 'is_active' => true, 'sort_order' => 2],
        );

        $color = Attribute::query()->firstOrCreate(
            ['code' => 'color'],
            [
                'name' => 'Color',
                'type' => AttributeType::Variant,
                'input_type' => AttributeInputType::Select,
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $this->seedOptions($color, ['Red', 'Blue', 'Black', 'White']);

        $size = Attribute::query()->firstOrCreate(
            ['code' => 'size'],
            [
                'name' => 'Size',
                'type' => AttributeType::Variant,
                'input_type' => AttributeInputType::Select,
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        $this->seedOptions($size, ['S', 'M', 'L', 'XL']);

        $material = Attribute::query()->firstOrCreate(
            ['code' => 'material'],
            [
                'name' => 'Material',
                'type' => AttributeType::Informational,
                'input_type' => AttributeInputType::Select,
                'is_active' => true,
                'sort_order' => 3,
            ],
        );

        $this->seedOptions($material, ['Cotton', 'Polyester', 'Blend']);

        Attribute::query()->firstOrCreate(
            ['code' => 'country_of_origin'],
            [
                'name' => 'Country of Origin',
                'type' => AttributeType::Informational,
                'input_type' => AttributeInputType::Text,
                'is_active' => true,
                'sort_order' => 4,
            ],
        );

        $brand = Brand::query()->where('slug', 'generic')->first();
        $tshirtCategory = Category::query()->where('slug', 'men-t-shirt')->first();
        $family = ProductFamily::query()->where('slug', 't-shirt')->first();

        $this->seedProduct([
            'sku' => 'BB-RICE-25',
            'name' => 'Miniket Rice 25kg',
            'slug' => 'miniket-rice-25kg',
            'description' => 'Premium miniket rice for daily cooking.',
            'barcode' => '890100000001',
            'category_id' => $grocery->id,
            'brand_id' => $brand?->id,
            'unit_id' => $piece->id,
            'published' => true,
        ]);

        $this->seedProduct([
            'sku' => 'BB-OIL-5',
            'name' => 'Soybean Oil 5L',
            'slug' => 'soybean-oil-5l',
            'description' => 'Refined soybean cooking oil.',
            'barcode' => '890100000002',
            'category_id' => $grocery->id,
            'brand_id' => $brand?->id,
            'unit_id' => $piece->id,
            'published' => true,
        ]);

        $this->seedProduct([
            'sku' => 'BB-TEA-500',
            'name' => 'Premium Tea 500g',
            'slug' => 'premium-tea-500g',
            'description' => 'Strong breakfast tea for home and office.',
            'barcode' => '890100000003',
            'category_id' => $grocery->id,
            'brand_id' => $brand?->id,
            'unit_id' => $piece->id,
            'published' => true,
        ]);

        $this->seedProduct([
            'sku' => 'BB-DAL-1',
            'name' => 'Masoor Dal 1kg',
            'slug' => 'masoor-dal-1kg',
            'description' => 'Cleaned red lentils.',
            'barcode' => '890100000004',
            'category_id' => $grocery->id,
            'brand_id' => $brand?->id,
            'unit_id' => $piece->id,
            'published' => true,
        ]);

        $this->seedProduct([
            'sku' => 'BB-TS-001',
            'name' => 'Cotton Crew T-Shirt',
            'slug' => 'cotton-crew-t-shirt',
            'description' => 'Everyday cotton t-shirt from the apparel family.',
            'barcode' => '890100000005',
            'category_id' => $tshirtCategory?->id,
            'brand_id' => Brand::query()->where('slug', 'nike')->value('id'),
            'unit_id' => $piece->id,
            'family_id' => $family?->id,
            'published' => true,
        ]);

        $this->seedProduct([
            'sku' => 'BB-DRAFT-1',
            'name' => 'Upcoming Festival Hamper',
            'slug' => 'upcoming-festival-hamper',
            'description' => 'Draft bundle — not yet on the storefront.',
            'category_id' => $grocery->id,
            'brand_id' => $brand?->id,
            'unit_id' => $piece->id,
            'published' => false,
        ]);

        $settings = app(CatalogSettingsService::class)->get();
        $settings->fill([
            'default_product_status' => ProductStatus::Draft,
            'default_publication_status' => PublicationStatus::NotPublished,
            'default_unit_id' => $piece->id,
            'sku_prefix' => 'BB-',
            'max_media_per_product' => 10,
        ])->save();

        $carton = Unit::query()->where('code', 'ctn')->first();
        if ($carton && $piece) {
            UnitConversion::query()->firstOrCreate(
                [
                    'from_unit_id' => $carton->id,
                    'to_unit_id' => $piece->id,
                ],
                ['factor' => 12],
            );
        }
    }

    /**
     * @param  array{
     *     sku: string,
     *     name: string,
     *     slug: string,
     *     description?: string,
     *     barcode?: string,
     *     category_id?: int|null,
     *     brand_id?: int|null,
     *     unit_id?: int|null,
     *     family_id?: int|null,
     *     published: bool
     * }  $data
     */
    private function seedProduct(array $data): void
    {
        $product = Product::query()->firstOrCreate(
            ['sku' => $data['sku']],
            [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'type' => ProductType::Simple,
                'status' => $data['published'] ? ProductStatus::Active : ProductStatus::Draft,
                'publication_status' => $data['published'] ? PublicationStatus::Published : PublicationStatus::NotPublished,
                'primary_category_id' => $data['category_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'product_family_id' => $data['family_id'] ?? null,
                'sort_order' => 0,
            ],
        );

        if (! empty($data['category_id'])) {
            $product->categories()->syncWithoutDetaching([$data['category_id']]);
        }
    }

    /**
     * @param  list<string>  $values
     */
    private function seedOptions(Attribute $attribute, array $values): void
    {
        foreach ($values as $index => $value) {
            AttributeOption::query()->firstOrCreate(
                [
                    'attribute_id' => $attribute->id,
                    'value' => $value,
                ],
                [
                    'code' => strtolower($value),
                    'sort_order' => $index,
                ],
            );
        }
    }
}
