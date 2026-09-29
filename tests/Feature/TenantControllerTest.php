<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Enums\PlanCode;
use Modules\Billing\Models\Plan;
use Modules\Billing\Services\PlanService;
use Modules\Commerce\Models\PriceList;
use Modules\Inventory\Models\Warehouse;
use Tests\TestCase;

class TenantControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_provisions_a_second_shop_with_its_own_default_warehouse_and_price_list(): void
    {
        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        $defaultTenant = Tenant::query()->where('slug', 'default')->firstOrFail();
        Warehouse::query()->create(['name' => 'Main Warehouse', 'code' => 'MAIN', 'is_active' => true, 'is_default' => true]);
        PriceList::query()->create(['name' => 'Retail', 'code' => 'retail', 'currency' => 'BDT', 'is_active' => true, 'is_default' => true]);

        $response = $this->actingAs(User::factory()->platformAdmin()->create())
            ->post(route('platform.tenants.store'), [
                'name' => 'Second Shop',
                'domain' => 'second.test',
                'owner_name' => 'Second Owner',
                'owner_email' => 'owner@second.test',
                'owner_password' => 'password123',
                'plan_id' => Plan::query()->where('code', PlanCode::Pro->value)->value('id'),
            ]);

        $tenant = Tenant::query()->where('slug', 'second-shop')->firstOrFail();
        $response->assertSessionHasNoErrors()->assertRedirect(route('platform.tenants.show', $tenant));
        $this->assertDatabaseHas('users', ['tenant_id' => $tenant->id, 'email' => 'owner@second.test', 'is_platform_admin' => false]);
        $this->assertDatabaseHas('warehouses', ['tenant_id' => $tenant->id, 'code' => 'MAIN']);
        $this->assertDatabaseHas('price_lists', ['tenant_id' => $tenant->id, 'code' => 'retail']);
        $this->assertDatabaseHas('warehouses', ['tenant_id' => $defaultTenant->id, 'code' => 'MAIN']);
        $this->assertDatabaseHas('price_lists', ['tenant_id' => $defaultTenant->id, 'code' => 'retail']);
    }
}
