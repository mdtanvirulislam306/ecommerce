<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\CatalogSetting;
use Modules\Catalog\Models\Unit;
use Modules\Catalog\Models\UnitConversion;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seeder_creates_carton_to_piece_conversion(): void
    {
        $this->seed(CatalogSeeder::class);

        $piece = Unit::query()->where('code', 'pcs')->first();
        $carton = Unit::query()->where('code', 'ctn')->first();

        $this->assertNotNull($piece);
        $this->assertNotNull($carton);
        $this->assertTrue(
            UnitConversion::query()
                ->where('from_unit_id', $carton->id)
                ->where('to_unit_id', $piece->id)
                ->where('factor', 12)
                ->exists(),
        );
    }

    public function test_catalog_seeder_creates_default_product_settings(): void
    {
        $this->seed(CatalogSeeder::class);

        $settings = CatalogSetting::query()->first();

        $this->assertNotNull($settings);
        $this->assertSame('draft', $settings->default_product_status->value);
        $this->assertSame('not_published', $settings->default_publication_status->value);
        $this->assertSame('BB-', $settings->sku_prefix);
        $this->assertSame(
            Unit::query()->where('code', 'pcs')->value('id'),
            $settings->default_unit_id,
        );
    }
}
