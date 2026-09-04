<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\EcommerceCoupon;
use Modules\Ecommerce\Models\StoreDomain;
use Modules\Ecommerce\Models\StoreSetting;
use Tests\TestCase;

class EcommerceAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_store_dashboard_loads(): void
    {
        StoreDomain::query()->create(['domain' => 'shop.test', 'is_active' => true]);

        $response = $this->actingAs($this->user)->get(route('ecommerce.store.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Ecommerce/Store/Dashboard')
            ->has('stats.domains')
        );
    }

    public function test_store_settings_can_be_saved(): void
    {
        $response = $this->actingAs($this->user)->put(route('ecommerce.store.settings.update'), [
            'store_name' => 'My Shop',
            'support_email' => 'help@shop.test',
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('store_settings', ['key' => 'store_name', 'value' => 'My Shop']);
        $this->assertDatabaseHas('store_settings', ['key' => 'currency', 'value' => 'BDT']);
    }

    public function test_store_domain_crud(): void
    {
        $create = $this->actingAs($this->user)->post(route('ecommerce.store.domains.store'), [
            'domain' => 'store.example.com',
            'is_primary' => true,
            'is_active' => true,
            'ssl_status' => 'active',
        ]);

        $create->assertRedirect();
        $domain = StoreDomain::query()->where('domain', 'store.example.com')->first();
        $this->assertNotNull($domain);

        $update = $this->actingAs($this->user)->put(route('ecommerce.store.domains.update', $domain), [
            'domain' => 'shop.example.com',
            'is_primary' => true,
            'is_active' => true,
            'ssl_status' => 'pending',
        ]);

        $update->assertRedirect();
        $this->assertDatabaseHas('store_domains', ['domain' => 'shop.example.com', 'ssl_status' => 'pending']);

        $delete = $this->actingAs($this->user)->delete(route('ecommerce.store.domains.destroy', $domain));
        $delete->assertRedirect();
        $this->assertDatabaseMissing('store_domains', ['id' => $domain->id]);
    }

    public function test_theme_can_be_installed_and_published(): void
    {
        $install = $this->actingAs($this->user)->post(route('ecommerce.theme.library.install'), [
            'code' => 'classic',
        ]);

        $install->assertRedirect();
        $this->assertDatabaseHas('theme_settings', ['code' => 'classic', 'is_installed' => true]);

        $publish = $this->actingAs($this->user)->post(route('ecommerce.theme.publish'));
        $publish->assertRedirect();
        $this->assertDatabaseHas('theme_settings', ['code' => 'classic', 'is_published' => true]);
    }

    public function test_coupon_crud(): void
    {
        $create = $this->actingAs($this->user)->post(route('ecommerce.coupons.store'), [
            'code' => 'save10',
            'name' => 'Save 10%',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);

        $create->assertRedirect();
        $coupon = EcommerceCoupon::query()->where('code', 'SAVE10')->first();
        $this->assertNotNull($coupon);

        $update = $this->actingAs($this->user)->put(route('ecommerce.coupons.update', $coupon), [
            'code' => 'save15',
            'name' => 'Save 15%',
            'type' => 'percentage',
            'value' => 15,
            'is_active' => false,
        ]);

        $update->assertRedirect();
        $this->assertDatabaseHas('ecommerce_coupons', ['code' => 'SAVE15', 'is_active' => false]);

        $delete = $this->actingAs($this->user)->delete(route('ecommerce.coupons.destroy', $coupon));
        $delete->assertRedirect();
        $this->assertDatabaseMissing('ecommerce_coupons', ['id' => $coupon->id]);
    }

    public function test_shipping_settings_use_store_settings_table(): void
    {
        StoreSetting::query()->create(['key' => 'shipping_enabled', 'value' => '0']);

        $response = $this->actingAs($this->user)->put(route('ecommerce.shipping.update'), [
            'shipping_enabled' => true,
            'default_rate' => 50,
            'free_shipping_threshold' => 1000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('store_settings', ['key' => 'shipping_enabled', 'value' => '1']);
        $this->assertDatabaseHas('store_settings', ['key' => 'default_rate', 'value' => '50']);
    }
}
