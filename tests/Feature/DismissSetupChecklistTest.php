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
use Modules\Billing\Models\Plan;
use Modules\Billing\Services\PlanService;
use Modules\Platform\Services\TenantProvisionService;
use Modules\Settings\Models\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DismissSetupChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_dismisses_the_checklist_without_changing_completion(): void
    {
        $this->travelTo('2026-09-29 12:00:00');
        [$tenant, $owner] = $this->provisionShop('dismiss-shop');
        $this->saveShopName($owner, $tenant, 'Corner Shop');

        $this->actingAs($owner)
            ->from($this->shopUrl($tenant, '/admin/dashboard'))
            ->post($this->shopUrl($tenant, '/admin/setup-checklist/dismiss'))
            ->assertRedirectBack();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Shop dismiss-shop',
            'status' => Tenant::STATUS_ACTIVE,
            'setup_dismissed_at' => '2026-09-29 12:00:00',
        ]);
        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('role', 'owner')
                ->where('setupChecklist', $this->checklistWith([
                    'shop_name' => true,
                    'progress' => 1,
                    'setup_dismissed_at' => '2026-09-29T12:00:00+06:00',
                ]))
            );
    }

    public function test_request_fields_do_not_change_the_dismissed_timestamp_or_the_tenant(): void
    {
        $this->travelTo('2026-09-29 12:00:00');
        [$tenant, $owner] = $this->provisionShop('payload-shop');

        $this->actingAs($owner)
            ->from($this->shopUrl($tenant, '/admin/dashboard'))
            ->post($this->shopUrl($tenant, '/admin/setup-checklist/dismiss'), [
                'setup_dismissed_at' => '2000-01-01 00:00:00',
                'name' => 'Hijacked Shop',
                'status' => Tenant::STATUS_SUSPENDED,
                'notes' => 'not from the client',
                'completed' => true,
                'shop_name' => true,
            ])
            ->assertRedirectBack();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Shop payload-shop',
            'status' => Tenant::STATUS_ACTIVE,
            'notes' => null,
            'setup_dismissed_at' => '2026-09-29 12:00:00',
        ]);
        $this->assertDatabaseMissing('setting_values', [
            'tenant_id' => $tenant->id,
            'group' => 'general',
            'key' => 'shop_name',
        ]);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function refusedRoles(): array
    {
        return [
            'sales manager' => ['sales-manager', 'Sales Manager'],
            'sales manager underscore' => ['sales_manager', 'Sales Manager Underscore'],
            'cashier' => ['cashier', 'Cashier'],
        ];
    }

    #[DataProvider('refusedRoles')]
    public function test_non_owner_receives_403_when_dismissing_the_setup_checklist(string $slug, string $name): void
    {
        [$tenant] = $this->provisionShop('refused-'.$slug);
        $user = $this->userWithRole($tenant, $slug, $name);

        $this->actingAs($user)
            ->post($this->shopUrl($tenant, '/admin/setup-checklist/dismiss'))
            ->assertForbidden();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'setup_dismissed_at' => null,
        ]);
    }

    public function test_dismissing_one_shop_does_not_change_another_tenant(): void
    {
        $this->travelTo('2026-09-29 12:00:00');
        [$home, $homeOwner] = $this->provisionShop('home-dismiss');
        [$other, $otherOwner] = $this->makeShop('other-dismiss');

        $this->actingAs($homeOwner)
            ->from($this->shopUrl($home, '/admin/dashboard'))
            ->post($this->shopUrl($home, '/admin/setup-checklist/dismiss'))
            ->assertRedirectBack();

        $this->assertDatabaseHas('tenants', [
            'id' => $home->id,
            'setup_dismissed_at' => '2026-09-29 12:00:00',
        ]);
        $this->assertDatabaseHas('tenants', [
            'id' => $other->id,
            'setup_dismissed_at' => null,
        ]);
        $this->actingAs($otherOwner)
            ->get($this->shopUrl($other, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('setupChecklist', $this->incompleteChecklist())
            );
    }

    public function test_user_from_another_shop_receives_403_when_dismissing_this_checklist(): void
    {
        [$home] = $this->provisionShop('target-shop');
        [, $otherOwner] = $this->makeShop('intruder-shop');

        $this->actingAs($otherOwner)
            ->post($this->shopUrl($home, '/admin/setup-checklist/dismiss'))
            ->assertForbidden();

        $this->assertDatabaseHas('tenants', [
            'id' => $home->id,
            'setup_dismissed_at' => null,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_dismissing_the_setup_checklist(): void
    {
        [$tenant] = $this->provisionShop('guest-shop');

        $this->post($this->shopUrl($tenant, '/admin/setup-checklist/dismiss'))
            ->assertRedirect(route('login', absolute: false));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'setup_dismissed_at' => null,
        ]);
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function provisionShop(string $slug): array
    {
        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));

        $plan = Plan::query()->where('code', PlanCode::Free->value)->firstOrFail();

        $tenant = app(TenantProvisionService::class)->provision([
            'name' => 'Shop '.$slug,
            'slug' => $slug,
            'domain' => $slug.'.test',
            'owner_name' => 'Shop Owner',
            'owner_email' => $slug.'@example.com',
            'owner_password' => 'password',
            'plan_id' => $plan->id,
        ]);

        $owner = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('email', $slug.'@example.com')
            ->firstOrFail();

        return [$tenant, $owner];
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function makeShop(string $slug): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Shop '.$slug,
            'slug' => $slug,
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'domain' => $slug.'.test',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => $slug.'@example.com',
            'is_platform_admin' => false,
        ]);

        return [$tenant, $owner];
    }

    private function userWithRole(Tenant $tenant, string $slug, string $name): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_platform_admin' => false,
        ]);

        app(TenantContext::class)->set($tenant);
        $role = Role::query()->create([
            'name' => $name,
            'slug' => $slug,
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function saveShopName(User $owner, Tenant $tenant, string $shopName): void
    {
        $this->actingAs($owner)
            ->from($this->shopUrl($tenant, '/admin/dashboard'))
            ->put($this->shopUrl($tenant, '/admin/settings/general'), [
                'shop_name' => $shopName,
                'timezone' => 'Asia/Dhaka',
                'locale' => 'en',
                'default_currency' => 'BDT',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    private function shopUrl(Tenant $tenant, string $path): string
    {
        $domain = TenantDomain::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->value('domain');

        return 'http://'.$domain.$path;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{
     *     shop_name: bool,
     *     business_profile: bool,
     *     store_settings: bool,
     *     payment_method: bool,
     *     completed: bool,
     *     progress: int,
     *     setup_dismissed_at: string|null
     * }
     */
    private function checklistWith(array $overrides): array
    {
        return array_replace($this->incompleteChecklist(), $overrides);
    }

    /**
     * @return array{
     *     shop_name: bool,
     *     business_profile: bool,
     *     store_settings: bool,
     *     payment_method: bool,
     *     completed: bool,
     *     progress: int,
     *     setup_dismissed_at: string|null
     * }
     */
    private function incompleteChecklist(): array
    {
        return [
            'shop_name' => false,
            'business_profile' => false,
            'store_settings' => false,
            'payment_method' => false,
            'completed' => false,
            'progress' => 0,
            'setup_dismissed_at' => null,
        ];
    }
}
