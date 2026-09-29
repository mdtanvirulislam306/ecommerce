<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Billing\Enums\PlanCode;
use Modules\Billing\Enums\SubscriptionStatus;
use Modules\Billing\Models\Plan;
use Modules\Billing\Models\Subscription;
use Modules\Billing\Services\PlanService;
use Modules\Settings\Models\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function tenantRoles(): array
    {
        return [
            'owner' => [null, null],
            'sales manager' => ['sales-manager', 'Sales Manager'],
        ];
    }

    #[DataProvider('tenantRoles')]
    public function test_tenant_user_cannot_update_a_global_plan(?string $roleSlug, ?string $roleName): void
    {
        $this->seedPlans();
        $tenant = $this->shop('plan-lock');
        $user = $this->tenantUser($tenant, $roleSlug, $roleName);
        $plan = $this->plan(PlanCode::Free);
        $before = $this->planDefinitions();

        $this->actingAs($user)
            ->put($this->shopUrl($tenant, '/admin/billing/plans/'.$plan->id), $this->planPayload())
            ->assertForbidden();

        $this->assertSame($before, $this->planDefinitions());
    }

    #[DataProvider('tenantRoles')]
    public function test_tenant_user_cannot_assign_a_plan(?string $roleSlug, ?string $roleName): void
    {
        $this->seedPlans();
        $tenant = $this->shop('plan-assign-lock');
        $user = $this->tenantUser($tenant, $roleSlug, $roleName);
        $free = $this->plan(PlanCode::Free);
        $pro = $this->plan(PlanCode::Pro);
        $subscription = $this->subscribe($tenant, $free);
        $before = $this->planDefinitions();

        $this->actingAs($user)
            ->post($this->shopUrl($tenant, '/admin/billing/plans/'.$pro->id.'/assign'))
            ->assertForbidden();

        $this->assertSame($before, $this->planDefinitions());
        $fresh = Subscription::query()->withoutGlobalScopes()->findOrFail($subscription->id);
        $this->assertSame($free->id, $fresh->plan_id);
        $this->assertSame(SubscriptionStatus::Active, $fresh->status);
        $this->assertNull($fresh->ends_at);
        $this->assertNull($fresh->payment_note);
    }

    public function test_platform_admin_can_update_a_plan_and_its_modules(): void
    {
        $this->seedPlans();
        $tenant = $this->shop('plan-edit');
        $admin = $this->platformAdmin();
        $free = $this->plan(PlanCode::Free);
        $pro = $this->plan(PlanCode::Pro);
        $proName = $pro->name;
        $proPrice = $pro->price_monthly;
        $proModules = $this->moduleCodes($pro);
        $index = $this->shopUrl($tenant, '/admin/billing/plans');

        $this->actingAs($admin)
            ->from($index)
            ->put($this->shopUrl($tenant, '/admin/billing/plans/'.$free->id), $this->planPayload())
            ->assertRedirect($index)
            ->assertSessionHas('success', 'Plan updated.');

        $free->refresh();
        $this->assertSame('Starter', $free->name);
        $this->assertSame('Edited by the platform.', $free->description);
        $this->assertSame(1500, $free->price_monthly);
        $this->assertSame(false, $free->is_active);
        $this->assertSame(PlanCode::Free->value, $free->code);
        $this->assertSame(['catalog', 'commerce'], $this->moduleCodes($free));

        $pro->refresh();
        $this->assertSame($proName, $pro->name);
        $this->assertSame($proPrice, $pro->price_monthly);
        $this->assertSame($proModules, $this->moduleCodes($pro));
    }

    public function test_platform_admin_can_assign_the_current_shops_plan(): void
    {
        $this->freezeTime();
        $this->seedPlans();
        $tenant = $this->shop('plan-switch');
        $admin = $this->platformAdmin();
        $free = $this->plan(PlanCode::Free);
        $pro = $this->plan(PlanCode::Pro);
        $original = $this->subscribe($tenant, $free);
        $index = $this->shopUrl($tenant, '/admin/billing/plans');

        $this->actingAs($admin)
            ->from($index)
            ->post($this->shopUrl($tenant, '/admin/billing/plans/'.$pro->id.'/assign'))
            ->assertRedirect($index)
            ->assertSessionHas('success', 'Shop switched to Pro.');

        $current = Subscription::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', SubscriptionStatus::Active)
            ->first();

        $this->assertNotNull($current);
        $this->assertSame($pro->id, $current->plan_id);
        $this->assertNull($current->ends_at);
        $this->assertNull($current->payment_note);

        $original->refresh();
        $this->assertSame(SubscriptionStatus::Cancelled, $original->status);
        $this->assertSame(now()->toDateTimeString(), $original->ends_at?->toDateTimeString());
    }

    public function test_platform_admin_assigns_a_plan_with_manual_ends_at(): void
    {
        $this->seedPlans();
        $tenant = $this->shop('manual-billing');
        $admin = $this->platformAdmin();
        $free = $this->plan(PlanCode::Free);
        $pro = $this->plan(PlanCode::Pro);
        $this->subscribe($tenant, $free);
        $before = $this->planDefinitions();

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'plan_id' => $pro->id,
                'ends_at' => '2026-12-31',
                'payment_note' => 'Manual invoice 42',
            ])
            ->assertRedirect(route('platform.tenants.show', $tenant))
            ->assertSessionHas('success', 'Tenant updated.');

        $this->assertSame($before, $this->planDefinitions());

        $current = Subscription::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', SubscriptionStatus::Active)
            ->first();

        $this->assertNotNull($current);
        $this->assertSame($pro->id, $current->plan_id);
        $this->assertSame('2026-12-31', $current->ends_at?->toDateString());
        $this->assertSame('Manual invoice 42', $current->payment_note);
    }

    public function test_tenant_owner_can_read_subscription_and_enabled_modules(): void
    {
        $this->seedPlans();
        $tenant = $this->shop('plan-read');
        $owner = $this->tenantUser($tenant, null, null);
        $free = $this->plan(PlanCode::Free);
        $this->subscribe($tenant, $free);

        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/billing/plans'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Billing/Plans/Index', false)
                ->where('subscription.plan_code', PlanCode::Free->value)
                ->where('subscription.plan_name', 'Free')
                ->where('subscription.status', SubscriptionStatus::Active->value)
                ->where('modules', function ($modules): bool {
                    $rows = collect($modules);
                    $sales = $rows->firstWhere('code', 'sales');
                    $crm = $rows->firstWhere('code', 'crm');

                    return is_array($sales)
                        && $sales['enabled'] === true
                        && is_array($crm)
                        && $crm['enabled'] === false;
                })
                ->where('enabledModules', fn ($codes) => collect($codes)->contains('sales')
                    && ! collect($codes)->contains('crm'))
            );
    }

    public function test_guest_is_redirected_from_plan_update(): void
    {
        $this->seedPlans();
        $plan = $this->plan(PlanCode::Free);
        $before = $this->planDefinitions();

        $this->put(route('billing.plans.update', $plan), $this->planPayload())
            ->assertRedirect(route('login'));

        $this->assertSame($before, $this->planDefinitions());
    }

    private function seedPlans(): void
    {
        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
    }

    private function plan(PlanCode $code): Plan
    {
        return Plan::query()->where('code', $code->value)->firstOrFail();
    }

    private function shop(string $slug): Tenant
    {
        $tenant = Tenant::query()->create([
            'name' => 'Shop '.$slug,
            'slug' => $slug,
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'domain' => $slug.'.test',
            'is_primary' => true,
            'is_active' => true,
        ]);

        return $tenant;
    }

    private function tenantUser(Tenant $tenant, ?string $roleSlug, ?string $roleName): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_platform_admin' => false,
        ]);

        if ($roleSlug !== null && $roleName !== null) {
            app(TenantContext::class)->set($tenant);

            $role = Role::query()->create([
                'name' => $roleName,
                'slug' => $roleSlug,
            ]);

            $user->roles()->attach($role->id);
        }

        return $user;
    }

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'is_platform_admin' => true,
            'tenant_id' => null,
        ]);
    }

    private function subscribe(Tenant $tenant, Plan $plan): Subscription
    {
        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => '2026-01-01 00:00:00',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function planPayload(): array
    {
        return [
            'name' => 'Starter',
            'description' => 'Edited by the platform.',
            'price_monthly' => 1500,
            'is_active' => false,
            'module_codes' => ['commerce', 'catalog'],
        ];
    }

    /**
     * @return list<string>
     */
    private function moduleCodes(Plan $plan): array
    {
        return $plan->modules()->orderBy('module_code')->pluck('module_code')->all();
    }

    /**
     * @return list<array{id: int, name: string, code: string, description: ?string, price_monthly: int, is_active: bool, is_default: bool, modules: list<string>}>
     */
    private function planDefinitions(): array
    {
        return Plan::query()
            ->with('modules')
            ->orderBy('id')
            ->get()
            ->map(fn (Plan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'is_active' => $plan->is_active,
                'is_default' => $plan->is_default,
                'modules' => $plan->modules->pluck('module_code')->sort()->values()->all(),
            ])
            ->all();
    }

    private function shopUrl(Tenant $tenant, string $path): string
    {
        $domain = TenantDomain::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->value('domain');

        return 'http://'.$domain.$path;
    }
}
