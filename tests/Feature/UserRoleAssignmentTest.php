<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Services\PlanService;
use Modules\Settings\Models\Role;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $otherShop;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        $this->owner = User::factory()->create();
        $this->otherShop = Tenant::query()->create(['name' => 'Other', 'slug' => 'other', 'status' => Tenant::STATUS_ACTIVE]);
    }

    public function test_owner_assigns_roles_to_shop_staff(): void
    {
        $staff = User::factory()->staff()->create();
        $role = Role::query()->create(['name' => 'Cashier', 'slug' => 'cashier']);

        $this->actingAs($this->owner)
            ->put(route('settings.users.assign-roles', $staff->id), ['role_ids' => [$role->id]])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame([$role->id], $staff->roles()->pluck('roles.id')->all());
    }

    public function test_user_of_another_shop_cannot_be_modified(): void
    {
        $outsider = User::factory()->staff()->create(['tenant_id' => $this->otherShop->id]);
        $role = Role::query()->create(['name' => 'Cashier', 'slug' => 'cashier']);

        $this->actingAs($this->owner)
            ->put(route('settings.users.assign-roles', $outsider->id), ['role_ids' => [$role->id]])
            ->assertNotFound();

        $this->assertDatabaseMissing('role_user', ['user_id' => $outsider->id]);
    }

    public function test_role_of_another_shop_cannot_be_assigned(): void
    {
        $staff = User::factory()->staff()->create();
        $defaultShop = app(TenantContext::class)->get();
        app(TenantContext::class)->set($this->otherShop);
        $foreignRole = Role::query()->create(['name' => 'Manager', 'slug' => 'manager']);
        app(TenantContext::class)->set($defaultShop);

        $this->actingAs($this->owner)
            ->put(route('settings.users.assign-roles', $staff->id), ['role_ids' => [$foreignRole->id]])
            ->assertSessionHasErrors(['role_ids.0' => 'The selected role_ids.0 is invalid.']);

        $this->assertDatabaseMissing('role_user', ['user_id' => $staff->id]);
    }
}
