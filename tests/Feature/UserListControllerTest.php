<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Billing\Services\PlanService;
use Tests\TestCase;

class UserListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_users_of_the_current_shop(): void
    {
        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        $otherShop = Tenant::query()->create(['name' => 'Other', 'slug' => 'other', 'status' => Tenant::STATUS_ACTIVE]);
        $shopOwner = User::factory()->create(['name' => 'Default Owner']);
        User::factory()->create(['name' => 'Default Cashier']);
        User::factory()->create(['name' => 'Other Shop Owner', 'tenant_id' => $otherShop->id]);
        User::factory()->platformAdmin()->create(['name' => 'Platform Owner']);

        $this->actingAs($shopOwner)
            ->get(route('settings.users.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Settings/Users/Index', false)
                ->where('users.data', fn ($users) => collect($users)->pluck('name')->sort()->values()->all() === ['Default Cashier', 'Default Owner'])
            );
    }
}
