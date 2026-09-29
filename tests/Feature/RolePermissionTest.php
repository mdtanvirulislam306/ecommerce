<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Billing\Enums\PlanCode;
use Modules\Billing\Models\Plan;
use Modules\Billing\Services\PlanService;
use Modules\Settings\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        app(PlanService::class)->assignPlan(Plan::query()->where('code', PlanCode::Free->value)->firstOrFail());

        $this->owner = User::factory()->create();
    }

    public function test_role_editor_offers_only_modules_in_the_shop_plan(): void
    {
        $this->actingAs($this->owner)
            ->get(route('settings.roles.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('options.permission_catalog', fn ($catalog) => collect($catalog)->pluck('code')->contains('sales')
                    && ! collect($catalog)->pluck('code')->contains('crm'))
            );
    }

    public function test_role_is_saved_with_its_permissions(): void
    {
        $this->actingAs($this->owner)
            ->post(route('settings.roles.store'), [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'permissions' => ['sales.orders.view', 'sales.orders.create'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(
            ['sales.orders.view', 'sales.orders.create'],
            Role::query()->where('slug', 'cashier')->firstOrFail()->permissions,
        );
    }

    public function test_role_cannot_grant_a_module_outside_the_plan(): void
    {
        $this->actingAs($this->owner)
            ->post(route('settings.roles.store'), [
                'name' => 'Sneaky',
                'slug' => 'sneaky',
                'permissions' => ['crm.leads.view'],
            ])
            ->assertSessionHasErrors(['permissions.0' => 'The selected permissions.0 is invalid.']);

        $this->assertDatabaseMissing('roles', ['slug' => 'sneaky']);
    }

    public function test_role_permissions_can_be_updated(): void
    {
        $role = Role::query()->create(['name' => 'Cashier', 'slug' => 'cashier', 'permissions' => ['sales.orders.view']]);

        $this->actingAs($this->owner)
            ->put(route('settings.roles.update', $role), [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'permissions' => [],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([], $role->fresh()->permissions);
    }
}
