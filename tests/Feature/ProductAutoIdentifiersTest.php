<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Tests\TestCase;

class ProductAutoIdentifiersTest extends TestCase
{
    use RefreshDatabase;

    public function test_simple_product_auto_generates_slug_sku_barcode_and_internal_code(): void
    {
        $this->seed(CatalogSeeder::class);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'type' => 'simple',
            'name' => 'Organic Honey Jar',
            'description' => 'Auto id test',
            'status' => 'draft',
            'publication_status' => 'not_published',
        ]);

        $response->assertRedirect();

        $product = Product::query()->where('name', 'Organic Honey Jar')->first();

        $this->assertNotNull($product);
        $this->assertSame('organic-honey-jar', $product->slug);
        $this->assertSame('ORGANIC_HONEY_JAR', $product->internal_code);
        $this->assertNotEmpty($product->sku);
        $this->assertSame($product->sku, $product->barcode);
        $this->assertStringContainsString('ORGANICHONEYJAR', str_replace(['-', '_'], '', $product->sku));
    }

    public function test_variant_product_auto_generates_variant_skus(): void
    {
        $this->seed(CatalogSeeder::class);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'type' => 'variant',
            'name' => 'Cotton Tee',
            'status' => 'draft',
            'publication_status' => 'not_published',
            'variants' => [
                ['attributes' => []],
                ['attributes' => []],
            ],
        ]);

        $response->assertRedirect();

        $product = Product::query()->where('name', 'Cotton Tee')->first();
        $this->assertNotNull($product);
        $this->assertNull($product->sku);

        $skus = ProductVariant::query()
            ->where('product_id', $product->id)
            ->orderBy('sort_order')
            ->pluck('sku')
            ->all();

        $this->assertCount(2, $skus);
        $this->assertNotSame($skus[0], $skus[1]);
        $this->assertNotEmpty($skus[0]);
        $this->assertNotEmpty($skus[1]);
    }
}
