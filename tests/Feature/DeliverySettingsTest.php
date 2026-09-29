<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Services\PlanService;
use Modules\Ecommerce\Services\DeliveryRateService;
use Tests\TestCase;

class DeliverySettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        $this->owner = User::factory()->create();
    }

    public function test_owner_saves_delivery_areas_and_free_threshold(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ecommerce.shipping.update'), [
                'shipping_enabled' => true,
                'zones' => [
                    ['name' => 'Inside Dhaka', 'rate' => 60],
                    ['name' => 'Outside Dhaka', 'rate' => 120],
                ],
                'free_shipping_threshold' => 2000,
            ])
            ->assertSessionHasNoErrors();

        $delivery = app(DeliveryRateService::class);
        $this->assertSame([
            ['code' => 'inside-dhaka', 'name' => 'Inside Dhaka', 'rate' => 60.0],
            ['code' => 'outside-dhaka', 'name' => 'Outside Dhaka', 'rate' => 120.0],
        ], $delivery->zones());
        $this->assertSame(2000.0, $delivery->freeThreshold());
    }

    public function test_two_areas_cannot_share_a_name(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ecommerce.shipping.update'), [
                'shipping_enabled' => true,
                'zones' => [
                    ['name' => 'Dhaka', 'rate' => 60],
                    ['name' => 'dhaka', 'rate' => 80],
                ],
            ])
            ->assertSessionHasErrors(['zones.0.name' => 'Each area needs a different name.']);
    }

    public function test_at_least_one_area_is_required_while_charging_for_delivery(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ecommerce.shipping.update'), ['shipping_enabled' => true, 'zones' => []])
            ->assertSessionHasErrors(['zones' => 'Add at least one delivery area.']);
    }
}
