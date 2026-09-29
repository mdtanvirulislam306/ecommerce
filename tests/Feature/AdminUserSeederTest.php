<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_a_platform_owner_who_can_sign_in_and_open_the_platform_console(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->post(route('login'), ['email' => 'admin@admin.com', 'password' => 'password'])
            ->assertSessionHasNoErrors();

        $owner = User::query()->where('email', 'admin@admin.com')->firstOrFail();
        $this->assertAuthenticatedAs($owner);
        $this->assertTrue($owner->isPlatformAdmin());
        $this->assertNull($owner->tenant_id);
        $this->get(route('platform.tenants.index'))->assertOk();
    }

    public function test_seeds_a_default_shop_owner_without_platform_access(): void
    {
        $this->seed(AdminUserSeeder::class);

        $shopOwner = User::query()->where('email', 'owner@admin.com')->firstOrFail();
        $this->assertSame(Tenant::query()->where('slug', 'default')->value('id'), $shopOwner->tenant_id);
        $this->assertFalse($shopOwner->isPlatformAdmin());
        $this->actingAs($shopOwner)->get(route('platform.tenants.index'))->assertForbidden();
    }
}
