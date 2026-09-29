<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Tenant\TenantContext;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia;
use Modules\Billing\Enums\PlanCode;
use Modules\Billing\Enums\SubscriptionStatus;
use Modules\Billing\Models\Plan;
use Modules\Billing\Models\Subscription;
use Modules\Billing\Services\PlanService;
use Modules\Platform\Services\PlatformSettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlatformConsoleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $defaultShop;

    private Plan $free;

    private Plan $pro;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        $this->defaultShop = app(TenantContext::class)->get();
        $this->admin = User::factory()->platformAdmin()->create(['name' => 'Platform Admin']);
        $this->free = Plan::query()->where('code', PlanCode::Free->value)->firstOrFail();
        $this->pro = Plan::query()->where('code', PlanCode::Pro->value)->firstOrFail();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function consolePages(): array
    {
        return [
            'dashboard' => ['platform.dashboard'],
            'users' => ['platform.users.index'],
            'plans' => ['platform.plans.index'],
            'settings' => ['platform.settings.edit'],
        ];
    }

    #[DataProvider('consolePages')]
    public function test_shop_owners_cannot_open_the_platform_console(string $routeName): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route($routeName))->assertForbidden();
    }

    public function test_dashboard_counts_revenue_only_from_running_plans_and_flags_shops_needing_attention(): void
    {
        $this->shopOn('Free Shop', $this->free);
        $this->shopOn('Lapsed Shop', $this->pro, endsAt: now()->subDay());
        $this->shopOn('Offline Shop', $this->pro, status: Tenant::STATUS_SUSPENDED);
        $this->shopOn('Renewing Shop', $this->pro, endsAt: now()->addDays(5));

        $this->actingAs($this->admin)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Platform/Dashboard/Index', false)
                ->where('shops.total', 5)
                ->where('shops.suspended', 1)
                ->where('revenue.mrr', 2 * $this->pro->price_monthly)
                ->where('revenue.paying_shops', 2)
                ->where('plan_mix.0.code', PlanCode::Free->value)
                ->where('plan_mix.0.shops', 1)
                ->where('plan_mix.1.shops', 2)
                ->where('plan_mix.1.mrr', 2 * $this->pro->price_monthly)
                ->where('attention', fn ($rows) => collect($rows)->map(fn ($row) => [$row['name'], $row['reason']])->all() === [
                    ['Lapsed Shop', 'expired'],
                    ['Renewing Shop', 'expiring'],
                    ['Offline Shop', 'suspended'],
                ])
            );
    }

    public function test_users_page_finds_people_across_shops(): void
    {
        $otherShop = $this->shopOn('Bazar Two', $this->free);
        User::factory()->create(['name' => 'Default Owner']);
        $otherOwner = User::factory()->create(['tenant_id' => $otherShop->id, 'name' => 'Bea Owner']);
        $otherStaff = User::factory()->staff()->create(['tenant_id' => $otherShop->id, 'name' => 'Sam Staff']);

        $this->actingAs($this->admin)
            ->get(route('platform.users.index', ['tenant_id' => $otherShop->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Platform/Users/Index', false)
                ->where('users.data', fn ($rows) => collect($rows)->pluck('id')->sort()->values()->all() === [$otherOwner->id, $otherStaff->id])
                ->where('summary.platform_admins', 1)
            );

        $this->actingAs($this->admin)
            ->get(route('platform.users.index', ['type' => 'staff', 'search' => 'sam']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.id', $otherStaff->id)
                ->where('users.data.0.shop.name', 'Bazar Two')
                ->where('users.data.0.can_impersonate', true)
            );
    }

    public function test_platform_admin_creates_a_plan_with_its_modules(): void
    {
        $this->actingAs($this->admin)
            ->post(route('platform.plans.store'), [
                'name' => 'Growth',
                'code' => ' Growth ',
                'description' => 'For growing shops.',
                'price_monthly' => 150000,
                'is_active' => true,
                'is_default' => false,
                'module_codes' => ['crm', 'catalog'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('platform.plans.index'));

        $plan = Plan::query()->where('code', 'growth')->firstOrFail();
        $this->assertSame(150000, $plan->price_monthly);
        $this->assertFalse($plan->is_default);
        $this->assertEqualsCanonicalizing(['crm', 'catalog'], $plan->moduleCodes());
    }

    public function test_plan_codes_must_be_unique_and_modules_must_exist(): void
    {
        $this->actingAs($this->admin)
            ->post(route('platform.plans.store'), [
                'name' => 'Pro again',
                'code' => PlanCode::Pro->value,
                'price_monthly' => 100,
                'module_codes' => ['not-a-module'],
            ])
            ->assertSessionHasErrors(['code', 'module_codes.0']);

        $this->assertSame(2, Plan::query()->count());
    }

    public function test_the_default_plan_cannot_be_hidden(): void
    {
        $this->actingAs($this->admin)
            ->put(route('platform.plans.update', $this->free), [
                'name' => 'Free',
                'price_monthly' => 0,
                'is_active' => false,
                'module_codes' => $this->free->moduleCodes(),
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($this->free->fresh()->is_active);
    }

    public function test_changing_a_plans_modules_reaches_its_shops_straight_away(): void
    {
        $shop = $this->shopOn('Free Shop', $this->free);
        $plans = app(PlanService::class);
        $this->assertNotContains('crm', $plans->enabledModuleCodesFromSubscription($shop->id));

        $this->actingAs($this->admin)
            ->put(route('platform.plans.update', $this->free), [
                'name' => 'Free',
                'price_monthly' => 0,
                'is_active' => true,
                'module_codes' => [...$this->free->moduleCodes(), 'crm'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertContains('crm', $plans->enabledModuleCodesFromSubscription($shop->id));
    }

    public function test_making_a_plan_the_default_moves_the_flag(): void
    {
        $this->actingAs($this->admin)
            ->post(route('platform.plans.make-default', $this->pro))
            ->assertSessionHas('success', 'New shops now start on Pro.');

        $this->assertTrue($this->pro->fresh()->is_default);
        $this->assertFalse($this->free->fresh()->is_default);
    }

    public function test_only_plans_no_shop_was_ever_billed_on_can_be_deleted(): void
    {
        $unused = app(PlanService::class)->create(['name' => 'Unused', 'code' => 'unused', 'price_monthly' => 0]);

        $this->actingAs($this->admin)->delete(route('platform.plans.destroy', $this->free))->assertSessionHasErrors('plan');
        $this->actingAs($this->admin)->delete(route('platform.plans.destroy', $this->pro))->assertSessionHasErrors('plan');
        $this->actingAs($this->admin)->delete(route('platform.plans.destroy', $unused))->assertSessionHasNoErrors();

        $this->assertModelExists($this->free);
        $this->assertModelExists($this->pro);
        $this->assertModelMissing($unused);
    }

    public function test_settings_store_secrets_encrypted_keep_them_when_left_blank_and_switch_the_mailer(): void
    {
        $this->actingAs($this->admin)
            ->put(route('platform.settings.update'), $this->settingsPayload(['mail_password' => 'smtp-secret']))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->put(route('platform.settings.update'), $this->settingsPayload(['mail_password' => '']))
            ->assertSessionHasNoErrors();

        $stored = PlatformSetting::query()->where('key', 'mail_password')->value('value');
        $this->assertNotSame('smtp-secret', $stored);
        $this->assertSame('smtp-secret', Crypt::decryptString($stored));
        $this->assertTrue($this->pro->fresh()->is_default);
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame('no-reply@example.com', config('mail.from.address'));
    }

    public function test_smtp_settings_require_a_server_and_sender(): void
    {
        $this->actingAs($this->admin)
            ->put(route('platform.settings.update'), $this->settingsPayload(['mail_host' => '', 'mail_from_address' => '']))
            ->assertSessionHasErrors(['mail_host', 'mail_from_address']);
    }

    public function test_test_email_is_sent_through_the_current_mailer(): void
    {
        $this->actingAs($this->admin)
            ->post(route('platform.settings.test-email'), ['email' => 'owner@example.com'])
            ->assertSessionHas('success', 'Test email sent to owner@example.com.');

        $messages = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertSame('owner@example.com', $messages->first()->getEnvelope()->getRecipients()[0]->getAddress());
    }

    public function test_maintenance_mode_takes_shops_offline_for_everyone_but_platform_admins(): void
    {
        $owner = User::factory()->create();
        app(PlatformSettingService::class)->putMany(['maintenance_enabled' => '1', 'maintenance_message' => 'Upgrading tonight.']);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertStatus(503)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Maintenance')
                ->where('message', 'Upgrading tonight.')
            );

        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('platform.dashboard'))->assertOk();
    }

    public function test_sign_in_page_stays_open_during_maintenance(): void
    {
        app(PlatformSettingService::class)->putMany(['maintenance_enabled' => '1']);

        $this->get(route('login'))->assertOk();
    }

    private function shopOn(string $name, Plan $plan, ?\DateTimeInterface $endsAt = null, string $status = Tenant::STATUS_ACTIVE): Tenant
    {
        $tenant = Tenant::query()->create(['name' => $name, 'slug' => str($name)->slug()->toString(), 'status' => $status]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subMonth(),
            'ends_at' => $endsAt,
        ]);

        return $tenant;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settingsPayload(array $overrides = []): array
    {
        return [
            'platform_name' => 'Bazar Cloud',
            'support_email' => 'help@example.com',
            'default_plan_id' => $this->pro->id,
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'mail_username' => 'mailer',
            'mail_password' => '',
            'mail_from_address' => 'no-reply@example.com',
            'mail_from_name' => 'Bazar Cloud',
            'sslcommerz_store_id' => 'bazar-store',
            'sslcommerz_store_password' => '',
            'sslcommerz_sandbox' => true,
            'maintenance_enabled' => false,
            'maintenance_message' => '',
            ...$overrides,
        ];
    }
}
