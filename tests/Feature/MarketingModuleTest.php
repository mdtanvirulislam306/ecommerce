<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketing\Enums\CampaignChannel;
use Modules\Marketing\Models\Campaign;
use Modules\Marketing\Models\MarketingCoupon;
use Modules\Marketing\Models\MarketingPromotion;
use Modules\Marketing\Models\Referral;
use Modules\Marketing\Models\Segment;
use Tests\TestCase;

class MarketingModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_overview_page_loads_with_stats(): void
    {
        Segment::query()->create(['name' => 'VIP', 'customer_count' => 10]);

        $response = $this->actingAs($this->user)->get(route('marketing.overview'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Marketing/Overview/Index')
            ->has('stats.segments', 1)
        );
    }

    public function test_email_channel_lists_only_email_campaigns(): void
    {
        Campaign::query()->create(['name' => 'Email blast', 'channel' => CampaignChannel::Email]);
        Campaign::query()->create(['name' => 'SMS alert', 'channel' => CampaignChannel::Sms]);

        $response = $this->actingAs($this->user)->get(route('marketing.email.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Marketing/Channels/Index')
            ->where('channel', 'email')
            ->has('campaigns.data', 1)
            ->where('campaigns.data.0.name', 'Email blast')
        );
    }

    public function test_email_channel_store_locks_channel(): void
    {
        $response = $this->actingAs($this->user)->post(route('marketing.email.store'), [
            'name' => 'Welcome series',
            'subject' => 'Hello',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('marketing_campaigns', [
            'name' => 'Welcome series',
            'channel' => 'email',
        ]);
    }

    public function test_channel_update_rejects_wrong_channel_campaign(): void
    {
        $campaign = Campaign::query()->create([
            'name' => 'SMS only',
            'channel' => CampaignChannel::Sms,
        ]);

        $response = $this->actingAs($this->user)->put(route('marketing.email.update', $campaign), [
            'name' => 'Hijacked',
            'status' => 'draft',
        ]);

        $response->assertNotFound();
    }

    public function test_segment_crud_persists(): void
    {
        $create = $this->actingAs($this->user)->post(route('marketing.segments.store'), [
            'name' => 'High spenders',
            'customer_count' => 42,
            'is_active' => true,
        ]);

        $create->assertRedirect();
        $segment = Segment::query()->first();
        $this->assertNotNull($segment);

        $update = $this->actingAs($this->user)->put(route('marketing.segments.update', $segment), [
            'name' => 'Big spenders',
            'customer_count' => 50,
            'is_active' => true,
        ]);

        $update->assertRedirect();
        $this->assertDatabaseHas('marketing_segments', ['name' => 'Big spenders', 'customer_count' => 50]);
    }

    public function test_promotion_and_coupon_crud_persists(): void
    {
        $this->actingAs($this->user)->post(route('marketing.promotions.store'), [
            'name' => 'Summer sale',
            'type' => 'percentage',
            'value' => 15,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('marketing_promotions', ['name' => 'Summer sale']);

        $this->actingAs($this->user)->post(route('marketing.coupons.store'), [
            'code' => 'SAVE10',
            'name' => 'Ten off',
            'type' => 'fixed',
            'value' => 10,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('marketing_coupons', ['code' => 'SAVE10']);
    }

    public function test_referral_and_reports_pages_load(): void
    {
        Referral::query()->create([
            'code' => 'REF123',
            'referrer_name' => 'Alice',
            'referrer_email' => 'alice@example.com',
            'reward_amount' => 25,
        ]);

        MarketingPromotion::query()->create([
            'name' => 'Promo',
            'type' => 'percentage',
            'value' => 5,
        ]);

        MarketingCoupon::query()->create([
            'code' => 'X',
            'name' => 'Coupon',
            'type' => 'fixed',
            'value' => 1,
        ]);

        $this->actingAs($this->user)->get(route('marketing.referrals.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Marketing/Referrals/Index'));

        $this->actingAs($this->user)->get(route('marketing.reports'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Marketing/Reports/Index')
                ->has('stats.referrals', 1)
            );
    }
}
