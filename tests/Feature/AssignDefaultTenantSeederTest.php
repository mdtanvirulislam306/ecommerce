<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Database\Seeders\AssignDefaultTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssignDefaultTenantSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_hands_rows_without_a_shop_to_the_default_shop_and_leaves_other_shops_alone(): void
    {
        $defaultTenantId = Tenant::query()->where('slug', 'default')->value('id');
        $otherTenant = Tenant::query()->create(['name' => 'Other', 'slug' => 'other', 'status' => Tenant::STATUS_ACTIVE]);
        DB::table('warehouses')->insert([
            ['tenant_id' => null, 'name' => 'Orphan', 'code' => 'ORPHAN', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $otherTenant->id, 'name' => 'Owned', 'code' => 'OWNED', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->seed(AssignDefaultTenantSeeder::class);

        $this->assertDatabaseHas('warehouses', ['code' => 'ORPHAN', 'tenant_id' => $defaultTenantId]);
        $this->assertDatabaseHas('warehouses', ['code' => 'OWNED', 'tenant_id' => $otherTenant->id]);
    }
}
