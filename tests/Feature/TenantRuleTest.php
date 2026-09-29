<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Enums\PlanCode;
use Modules\Billing\Models\Plan;
use Modules\Billing\Services\PlanService;
use Modules\Inventory\Models\Warehouse;
use Tests\TestCase;

class TenantRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
    }

    public function test_shop_can_reuse_a_code_that_another_shop_already_uses(): void
    {
        Warehouse::query()->create(['name' => 'Default Main', 'code' => 'MAIN', 'is_active' => true]);
        $otherShop = $this->shopOnProPlan('other-shop');

        $this->actingAs(User::factory()->create(['tenant_id' => $otherShop->id]))
            ->post('http://other-shop.test'.route('inventory.warehouses.store', absolute: false), [
                'name' => 'Other Main',
                'code' => 'MAIN',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('warehouses', ['tenant_id' => $otherShop->id, 'code' => 'MAIN', 'name' => 'Other Main']);
    }

    public function test_rejects_a_code_already_used_in_the_same_shop(): void
    {
        Warehouse::query()->create(['name' => 'Default Main', 'code' => 'MAIN', 'is_active' => true]);

        $this->actingAs(User::factory()->create())
            ->post(route('inventory.warehouses.store'), [
                'name' => 'Duplicate Main',
                'code' => 'MAIN',
            ])
            ->assertSessionHasErrors(['code' => 'The code has already been taken.']);

        $this->assertDatabaseMissing('warehouses', ['name' => 'Duplicate Main']);
    }

    private function shopOnProPlan(string $slug): Tenant
    {
        $tenant = Tenant::query()->create(['name' => 'Shop '.$slug, 'slug' => $slug, 'status' => Tenant::STATUS_ACTIVE]);
        TenantDomain::query()->create(['tenant_id' => $tenant->id, 'domain' => $slug.'.test', 'is_primary' => true, 'is_active' => true]);
        app(PlanService::class)->assignPlan(Plan::query()->where('code', PlanCode::Pro->value)->firstOrFail(), $tenant->id);

        return $tenant;
    }
}
