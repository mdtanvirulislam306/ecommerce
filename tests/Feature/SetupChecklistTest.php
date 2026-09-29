<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Services\SetupChecklistService;
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
use Tests\TestCase;

class SetupChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_freshly_provisioned_shop_has_an_incomplete_setup_checklist(): void
    {
        [$tenant, $owner] = $this->provisionShop('fresh-shop');

        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('role', 'owner')
                ->where('setupChecklist', $this->incompleteChecklist())
            );
    }

    public function test_owner_profile_includes_the_setup_checklist(): void
    {
        [$tenant, $owner] = $this->provisionShop('profile-shop');

        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/profile'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Profile/Edit')
                ->where('setupChecklist', $this->incompleteChecklist())
            );
    }

    public function test_filled_shop_name_marks_that_checklist_item_complete(): void
    {
        [$tenant, $owner] = $this->provisionShop('named-shop');

        $this->saveShopName($owner, $tenant, 'Corner Shop');

        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('setupChecklist', [
                    'shop_name' => true,
                    'business_profile' => false,
                    'store_settings' => false,
                    'payment_method' => false,
                    'completed' => false,
                    'progress' => 25,
                ])
            );
    }

    public function test_blank_shop_name_stays_incomplete_when_other_general_settings_are_saved(): void
    {
        [$tenant, $owner] = $this->provisionShop('blank-name');

        $this->saveShopName($owner, $tenant, '   ');

        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('setupChecklist', $this->incompleteChecklist())
            );
    }

    public function test_filled_business_profile_marks_that_checklist_item_complete(): void
    {
        [$tenant, $owner] = $this->provisionShop('profile-filled');

        $this->saveBusinessProfile($owner, $tenant, 'Corner Shop Ltd');

        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('setupChecklist', [
                    'shop_name' => false,
                    'business_profile' => true,
                    'store_settings' => false,
                    'payment_method' => false,
                    'completed' => false,
                    'progress' => 25,
                ])
            );
    }

    public function test_store_name_marks_store_settings_complete(): void
    {
        [$tenant, $owner] = $this->provisionShop('store-named');

        $this->saveStoreSettings($owner, $tenant, 'Corner Store');

        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('setupChecklist', [
                    'shop_name' => false,
                    'business_profile' => false,
                    'store_settings' => true,
                    'payment_method' => false,
                    'completed' => false,
                    'progress' => 25,
                ])
            );
    }

    public function test_store_seo_does_not_complete_store_settings(): void
    {
        [$tenant, $owner] = $this->provisionShop('seo-only');

        $this->actingAs($owner)
            ->from($this->shopUrl($tenant, '/admin/dashboard'))
            ->put($this->shopUrl($tenant, '/admin/ecommerce/store/seo'), [
                'meta_title' => 'Corner SEO',
                'meta_description' => 'A shop',
                'meta_keywords' => 'shop',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('setupChecklist', $this->incompleteChecklist())
            );
    }

    public function test_configured_payment_method_marks_that_checklist_item_complete(): void
    {
        [$tenant, $owner] = $this->provisionShop('payments');

        $this->savePaymentMethod($owner, $tenant, 'cod');

        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('setupChecklist', [
                    'shop_name' => false,
                    'business_profile' => false,
                    'store_settings' => false,
                    'payment_method' => true,
                    'completed' => false,
                    'progress' => 25,
                ])
            );
    }

    public function test_checklist_is_complete_when_every_area_is_filled(): void
    {
        [$tenant, $owner] = $this->provisionShop('ready-shop');

        $this->saveShopName($owner, $tenant, 'Corner Shop');
        $this->saveBusinessProfile($owner, $tenant, 'Corner Shop Ltd');
        $this->saveStoreSettings($owner, $tenant, 'Corner Store');
        $this->savePaymentMethod($owner, $tenant, 'cod');

        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('setupChecklist', [
                    'shop_name' => true,
                    'business_profile' => true,
                    'store_settings' => true,
                    'payment_method' => true,
                    'completed' => true,
                    'progress' => 100,
                ])
            );
    }

    public function test_sales_manager_dashboard_omits_the_setup_checklist(): void
    {
        [$tenant, $owner] = $this->provisionShop('sales-shop');
        $this->saveShopName($owner, $tenant, 'Corner Shop');

        app(TenantContext::class)->set($tenant);
        $role = Role::query()->create([
            'name' => 'Sales Manager',
            'slug' => 'sales-manager',
        ]);
        $owner->roles()->attach($role->id);

        $this->actingAs($owner)
            ->get($this->shopUrl($tenant, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('role', 'sales_manager')
                ->missing('setupChecklist')
            );
    }

    public function test_another_tenants_setup_data_does_not_complete_this_checklist(): void
    {
        [$home, $homeOwner] = $this->provisionShop('home-shop');
        [$other, $otherOwner] = $this->makeShop('other-shop');

        $this->saveShopName($otherOwner, $other, 'Other Name');
        $this->saveBusinessProfile($otherOwner, $other, 'Other Ltd');
        $this->saveStoreSettings($otherOwner, $other, 'Other Store');
        $this->savePaymentMethod($otherOwner, $other, 'other-cod');

        $this->assertDatabaseHas('setting_values', [
            'tenant_id' => $other->id,
            'group' => 'general',
            'key' => 'shop_name',
            'value' => 'Other Name',
        ]);
        $this->assertDatabaseHas('store_settings', [
            'tenant_id' => $other->id,
            'key' => 'store_name',
            'value' => 'Other Store',
        ]);
        $this->assertDatabaseHas('payment_methods', [
            'tenant_id' => $other->id,
            'code' => 'other-cod',
        ]);

        $this->actingAs($homeOwner)
            ->get($this->shopUrl($home, '/admin/dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('setupChecklist', $this->incompleteChecklist())
            );
    }

    public function test_checklist_stays_incomplete_when_no_tenant_is_bound(): void
    {
        [$tenant, $owner] = $this->provisionShop('unbound-shop');
        $this->saveShopName($owner, $tenant, 'Named Shop');

        app(TenantContext::class)->clear();

        $this->assertSame(
            $this->incompleteChecklist(),
            app(SetupChecklistService::class)->forCurrentTenant(),
        );
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
            'status' => 'active',
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

    private function saveBusinessProfile(User $owner, Tenant $tenant, string $companyName): void
    {
        $this->actingAs($owner)
            ->from($this->shopUrl($tenant, '/admin/dashboard'))
            ->put($this->shopUrl($tenant, '/admin/settings/business/company'), [
                'company_name' => $companyName,
                'legal_name' => '',
                'email' => '',
                'phone' => '',
                'address' => '',
                'tax_id' => '',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    private function saveStoreSettings(User $owner, Tenant $tenant, string $storeName): void
    {
        $this->actingAs($owner)
            ->from($this->shopUrl($tenant, '/admin/dashboard'))
            ->put($this->shopUrl($tenant, '/admin/ecommerce/store/settings'), [
                'store_name' => $storeName,
                'support_email' => 'hello@corner.test',
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    private function savePaymentMethod(User $owner, Tenant $tenant, string $code): void
    {
        $this->actingAs($owner)
            ->from($this->shopUrl($tenant, '/admin/dashboard'))
            ->post($this->shopUrl($tenant, '/admin/settings/payment-methods'), [
                'name' => 'Cash on Delivery',
                'code' => $code,
                'is_active' => true,
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
     * @return array{
     *     shop_name: bool,
     *     business_profile: bool,
     *     store_settings: bool,
     *     payment_method: bool,
     *     completed: bool,
     *     progress: int
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
        ];
    }
}
