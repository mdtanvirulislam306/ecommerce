<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Billing\Services\PlanService;
use Modules\Platform\Services\ImpersonationService;
use Modules\Settings\Models\AuditLog;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private Tenant $shop;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        $this->shop = app(TenantContext::class)->get();
        $this->admin = User::factory()->platformAdmin()->create(['name' => 'Platform Admin']);
        $this->owner = User::factory()->create(['name' => 'Shop Owner']);
    }

    public function test_platform_admin_signs_in_as_a_shop_user_through_a_single_use_link(): void
    {
        $link = $this->startImpersonating($this->owner);

        $this->assertStringContainsString('/admin/impersonate/', $link);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->shop->id,
            'user_id' => $this->admin->id,
            'action' => ImpersonationService::ACTION_STARTED,
            'subject_id' => $this->owner->id,
        ]);

        $this->get($link)->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->owner, 'web');
        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.id', $this->owner->id)
                ->where('impersonation.impersonator_name', 'Platform Admin')
            );

        $this->get($link)->assertForbidden();
    }

    public function test_link_points_at_the_shops_own_domain_and_works_there(): void
    {
        $otherShop = $this->shopWithDomain('other.test');
        $staff = $this->userOf($otherShop);

        $link = $this->startImpersonating($staff, $otherShop);

        $this->assertMatchesRegularExpression('#^http://other\.test(:\d+)?/admin/impersonate/[A-Za-z0-9]{64}$#', $link);
        $this->get($link)->assertRedirect();
        $this->assertAuthenticatedAs($staff, 'web');
    }

    public function test_link_cannot_be_used_on_a_different_shop(): void
    {
        $otherShop = $this->shopWithDomain('other.test');
        $staff = $this->userOf($otherShop);
        $link = $this->startImpersonating($staff, $otherShop);

        $this->get(preg_replace('#^http://other\.test(:\d+)?#', 'http://localhost', $link))->assertForbidden();

        $this->assertAuthenticatedAs($this->admin, 'web');
    }

    public function test_returning_to_the_platform_signs_the_admin_back_in_and_is_logged(): void
    {
        $this->get($this->startImpersonating($this->owner));

        $this->post(route('impersonation.leave'))
            ->assertRedirect(route('platform.tenants.show', $this->shop->id))
            ->assertSessionMissing(ImpersonationService::SESSION_KEY);

        $this->assertAuthenticatedAs($this->admin, 'web');
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->shop->id,
            'action' => ImpersonationService::ACTION_ENDED,
            'subject_id' => $this->owner->id,
        ]);
    }

    public function test_profile_and_password_are_locked_while_viewing_as_someone(): void
    {
        $this->get($this->startImpersonating($this->owner));

        $this->patch(route('profile.update'), ['name' => 'Hijacked', 'email' => $this->owner->email])->assertForbidden();
        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertForbidden();

        $this->assertSame('Shop Owner', $this->owner->fresh()->name);
    }

    public function test_shop_users_cannot_impersonate(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($this->owner)
            ->post(route('platform.tenants.impersonate', [$this->shop->id, $staff->id]))
            ->assertForbidden();

        $this->assertSame(0, AuditLog::query()->withoutGlobalScopes()->count());
    }

    public function test_deactivated_and_invited_users_cannot_be_impersonated(): void
    {
        $deactivated = User::factory()->staff()->deactivated()->create();
        $invited = User::factory()->invited()->create();

        foreach ([$deactivated, $invited] as $user) {
            $this->actingAs($this->admin)
                ->post(route('platform.tenants.impersonate', [$this->shop->id, $user->id]))
                ->assertSessionHasErrors('impersonation');
        }

        $this->assertSame(0, AuditLog::query()->withoutGlobalScopes()->count());
    }

    public function test_user_of_another_shop_cannot_be_reached_through_this_shop(): void
    {
        $outsider = $this->userOf($this->shopWithDomain('other.test'));

        $this->actingAs($this->admin)
            ->post(route('platform.tenants.impersonate', [$this->shop->id, $outsider->id]))
            ->assertNotFound();
    }

    public function test_tenant_page_lists_the_team_and_platform_access_history(): void
    {
        $this->startImpersonating($this->owner);

        $this->actingAs($this->admin)
            ->get(route('platform.tenants.show', $this->shop->id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Platform/Tenants/Show', false)
                ->where('team.0.id', $this->owner->id)
                ->where('team.0.can_impersonate', true)
                ->where('accessHistory.0.admin', 'Platform Admin')
                ->where('accessHistory.0.user', 'Shop Owner')
            );
    }

    private function startImpersonating(User $user, ?Tenant $tenant = null): string
    {
        $response = $this->actingAs($this->admin)
            ->post(route('platform.tenants.impersonate', [($tenant ?? $this->shop)->id, $user->id]))
            ->assertRedirect();

        return (string) $response->headers->get('Location');
    }

    private function shopWithDomain(string $domain): Tenant
    {
        $tenant = Tenant::query()->create(['name' => 'Other Shop', 'slug' => 'other', 'status' => Tenant::STATUS_ACTIVE]);
        TenantDomain::query()->create(['tenant_id' => $tenant->id, 'domain' => $domain, 'is_primary' => true, 'is_active' => true]);

        return $tenant;
    }

    private function userOf(Tenant $tenant): User
    {
        return User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Other Owner']);
    }
}
