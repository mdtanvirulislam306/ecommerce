<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Permission\PermissionRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Modules\Billing\Services\PlanService;
use Modules\Settings\Models\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function routePermissionKeys(): array
    {
        return [
            'listing page' => ['crm.leads.all', 'crm.leads.view'],
            'create page' => ['crm.leads.create', 'crm.leads.create'],
            'store' => ['crm.leads.store', 'crm.leads.create'],
            'update' => ['crm.leads.update', 'crm.leads.update'],
            'destroy' => ['crm.leads.destroy', 'crm.leads.delete'],
            'custom action' => ['crm.leads.convert', 'crm.leads.convert'],
            'module-wide page' => ['crm.overview', 'crm.general.view'],
            'route outside modules' => ['dashboard', null],
        ];
    }

    #[DataProvider('routePermissionKeys')]
    public function test_route_permission_key_is_derived_from_module_area_and_action(string $routeName, ?string $expectedKey): void
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertSame($expectedKey, app(PermissionRegistry::class)->keyForRoute($route));
    }

    public function test_staff_without_a_permission_is_forbidden(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('crm.leads.all'))
            ->assertForbidden();
    }

    public function test_staff_can_only_do_what_their_role_grants(): void
    {
        $staff = User::factory()->staff()->create();
        $staff->roles()->attach(Role::query()->create([
            'name' => 'Lead Viewer',
            'slug' => 'lead-viewer',
            'permissions' => ['crm.leads.view'],
        ]));

        $this->actingAs($staff)->get(route('crm.leads.all'))->assertOk();
        $this->actingAs($staff)->post(route('crm.leads.store'), ['name' => 'Blocked'])->assertForbidden();
        $this->assertDatabaseMissing('leads', ['name' => 'Blocked']);
    }

    public function test_shop_owner_and_platform_admin_have_full_access(): void
    {
        $owner = User::factory()->create();
        $platformAdmin = User::factory()->platformAdmin()->create();

        $this->actingAs($owner)->get(route('crm.leads.all'))->assertOk();
        $this->actingAs($platformAdmin)->get(route('crm.leads.all'))->assertOk();
    }

    public function test_staff_receive_the_pages_they_cannot_open(): void
    {
        $staff = User::factory()->staff()->create();
        $staff->roles()->attach(Role::query()->create([
            'name' => 'Lead Viewer',
            'slug' => 'lead-viewer',
            'permissions' => ['crm.leads.view'],
        ]));

        $this->actingAs($staff)
            ->get(route('crm.leads.all'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.full_access', false)
                ->where('auth.permissions', ['crm.leads.view'])
                ->where('auth.denied_paths', fn ($paths) => collect($paths)->contains('/admin/crm/leads/create')
                    && ! collect($paths)->contains('/admin/crm/leads/all'))
            );
    }

    public function test_owner_has_no_denied_pages(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('crm.leads.all'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.full_access', true)
                ->where('auth.denied_paths', [])
            );
    }
}
