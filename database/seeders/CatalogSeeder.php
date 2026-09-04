<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Enums\AttributeInputType;
use Modules\Catalog\Enums\AttributeType;
use Modules\Catalog\Models\Attribute;
use Modules\Catalog\Models\AttributeOption;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Collection;
use Modules\Catalog\Models\ProductFamily;
use Modules\Catalog\Models\Unit;
use Modules\Catalog\Models\UnitConversion;

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

        unset($piece);

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
