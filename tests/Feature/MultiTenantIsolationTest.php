<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Enums\SubscriptionStatus;
use Modules\Billing\Models\Plan;
use Modules\Billing\Models\Subscription;
use Modules\Billing\Services\PlanService;
use Modules\Catalog\Models\Product;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
    }

    public function test_products_are_isolated_by_tenant_host(): void
    {
        [$tenantA, $tenantB] = $this->makeTenantPair('shop-a', 'shop-b');

        $productA = DB::table('products')->insertGetId([
            'tenant_id' => $tenantA->id,
            'name' => 'Alpha Tea',
            'slug' => 'alpha-tea',
            'sku' => 'A-TEA',
            'type' => 'simple',
            'status' => 'active',
            'publication_status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'tenant_id' => $tenantB->id,
            'name' => 'Beta Coffee',
            'slug' => 'beta-coffee',
            'sku' => 'B-COF',
            'type' => 'simple',
            'status' => 'active',
            'publication_status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('http://shop-a.test/shop')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/Index', false)
                ->where('best_selling', fn ($items) => collect($items)->contains('id', $productA)
                    && ! collect($items)->contains(fn ($i) => ($i['name'] ?? '') === 'Beta Coffee'))
            );
    }

    public function test_suspended_tenant_is_blocked(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Frozen', 'slug' => 'frozen', 'status' => 'suspended']);
        TenantDomain::query()->create(['tenant_id' => $tenant->id, 'domain' => 'frozen.test', 'is_primary' => true, 'is_active' => true]);

        $this->get('http://frozen.test/shop')->assertForbidden();
    }

    public function test_expired_subscription_locks_storefront(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Expired', 'slug' => 'expired-shop', 'status' => 'active']);
        TenantDomain::query()->create(['tenant_id' => $tenant->id, 'domain' => 'expired.test', 'is_primary' => true, 'is_active' => true]);

        $plan = Plan::query()->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(),
        ]);

        $this->get('http://expired.test/shop')->assertForbidden();
    }

    public function test_product_idor_returns_not_found_on_other_tenant_host(): void
    {
        [$tenantA, $tenantB] = $this->makeTenantPair('idor-a', 'idor-b');

        $productB = Product::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Secret Soap',
            'slug' => 'secret-soap',
            'sku' => 'SEC-SOAP',
            'type' => 'simple',
            'status' => 'active',
            'publication_status' => 'published',
        ]);

        $ownerA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'is_platform_admin' => false,
        ]);

        $this->actingAs($ownerA)
            ->get('http://idor-a.test/products/'.$productB->id)
            ->assertNotFound();
    }

    public function test_platform_admin_can_open_tenants_index(): void
    {
        $admin = User::factory()->create([
            'is_platform_admin' => true,
            'tenant_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('platform.tenants.index'))
            ->assertOk();
    }

    public function test_non_platform_user_cannot_open_tenants_index(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'default'],
            ['name' => 'Default Shop', 'status' => 'active'],
        );

        $user = User::factory()->create([
            'is_platform_admin' => false,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user)
            ->get(route('platform.tenants.index'))
            ->assertForbidden();
    }

    /**
     * @return array{0: Tenant, 1: Tenant}
     */
    private function makeTenantPair(string $slugA, string $slugB): array
    {
        $tenantA = Tenant::query()->create(['name' => 'Shop '.$slugA, 'slug' => $slugA, 'status' => 'active']);
        $tenantB = Tenant::query()->create(['name' => 'Shop '.$slugB, 'slug' => $slugB, 'status' => 'active']);

        TenantDomain::query()->create(['tenant_id' => $tenantA->id, 'domain' => $slugA.'.test', 'is_primary' => true, 'is_active' => true]);
        TenantDomain::query()->create(['tenant_id' => $tenantB->id, 'domain' => $slugB.'.test', 'is_primary' => true, 'is_active' => true]);

        return [$tenantA, $tenantB];
    }
}
