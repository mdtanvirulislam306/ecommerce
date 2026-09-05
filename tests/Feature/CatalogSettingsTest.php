<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\CatalogSettingsService;
use Tests\TestCase;

class CatalogSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_edit_renders_when_settings_statuses_are_empty(): void
    {
        $this->seed(CatalogSeeder::class);

        DB::table('catalog_settings')->update([
            'default_product_status' => '',
            'default_publication_status' => '',
        ]);

        $product = Product::query()->where('sku', 'BB-RICE-25')->first();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('products.edit', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('options.catalog_defaults.status')
                ->where('options.catalog_defaults.status', ProductStatus::Draft->value)
            );
    }

    public function test_get_repairs_null_status_values_to_draft_defaults(): void
    {
        DB::table('catalog_settings')->insert([
            'default_product_status' => '',
            'default_publication_status' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settings = app(CatalogSettingsService::class)->get();

        $this->assertSame(ProductStatus::Draft, $settings->default_product_status);
        $this->assertSame(PublicationStatus::NotPublished, $settings->default_publication_status);
        $this->assertDatabaseHas('catalog_settings', [
            'id' => $settings->id,
            'default_product_status' => 'draft',
            'default_publication_status' => 'not_published',
        ]);
    }
}
