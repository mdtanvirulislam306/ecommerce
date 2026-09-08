<?php

namespace Tests\Feature;

use Database\Seeders\DemoStorefrontImagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoStorefrontImagesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_image_seeder_attaches_real_photos_to_matching_categories_and_products(): void
    {
        Storage::fake('public');

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Fruits & Vegetables',
            'slug' => 'fruits-vegetables',
            'image_path' => 'categories/fruits-vegetables.svg',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'name' => 'Fresh Banana (Dozen)',
            'slug' => 'fresh-banana-dozen',
            'sku' => 'DEMO-FRUITSVEGETABLES-01',
            'type' => 'simple',
            'status' => 'active',
            'publication_status' => 'published',
            'primary_category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_media')->insert([
            'product_id' => $productId,
            'path' => 'products/demo_old.svg',
            'type' => 'image',
            'is_primary' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(DemoStorefrontImagesSeeder::class);

        $this->assertDatabaseHas('categories', [
            'id' => $categoryId,
            'image_path' => 'categories/fruits-vegetables.jpg',
        ]);
        Storage::disk('public')->assertExists('categories/fruits-vegetables.jpg');

        $this->assertDatabaseHas('product_media', [
            'product_id' => $productId,
            'path' => 'products/demo-fruitsvegetables-01.jpg',
            'is_primary' => true,
        ]);
        Storage::disk('public')->assertExists('products/demo-fruitsvegetables-01.jpg');
        $this->assertDatabaseMissing('product_media', [
            'product_id' => $productId,
            'path' => 'products/demo_old.svg',
        ]);
    }
}
