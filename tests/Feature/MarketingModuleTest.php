<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Services\PlanService;
use Modules\Crm\Models\Customer;
use Modules\Crm\Models\CustomerSegment;
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

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));

        $this->user = User::factory()->create();
    }

    public function test_overview_page_loads_with_stats(): void
    {
        Segment::query()->create(['name' => 'VIP', 'customer_count' => 10]);

        $response = $this->actingAs($this->user)->get(route('marketing.overview'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Marketing/Overview/Index', false)
            ->where('stats.segments', 1)
        );
    }

    public function test_email_channel_lists_only_email_campaigns(): void
    {
        Campaign::query()->create(['name' => 'Email blast', 'channel' => CampaignChannel::Email]);
        Campaign::query()->create(['name' => 'SMS alert', 'channel' => CampaignChannel::Sms]);

        $response = $this->actingAs($this->user)->get(route('marketing.email.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Marketing/Channels/Index', false)
            ->where('channel', 'email')
            ->has('campaigns.data', 1)
            ->where('campaigns.data.0.name', 'Email blast')
            ->has('crmSegments')
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

    public function test_marketing_segment_linked_to_crm_uses_membership_count(): void
    {
        $crmSegment = $this->createCrmSegmentWithCustomers(2);

        $this->actingAs($this->user)->post(route('marketing.segments.store'), [
            'name' => 'CRM VIP list',
            'customer_segment_id' => $crmSegment->id,
            'customer_count' => 999,
            'is_active' => true,
        ])->assertRedirect();

        $segment = Segment::query()->where('name', 'CRM VIP list')->first();
        $this->assertNotNull($segment);
        $this->assertSame($crmSegment->id, $segment->customer_segment_id);
        $this->assertSame(2, $segment->customer_count);

        $this->actingAs($this->user)
            ->get(route('marketing.segments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Marketing/Segments/Index', false)
                ->where('segments.data.0.customer_count', 2)
                ->where('segments.data.0.is_linked_to_crm', true)
                ->where('segments.data.0.customer_segment_name', $crmSegment->name)
                ->has('crmSegments', 1)
            );
    }

    public function test_campaign_linked_to_crm_uses_membership_count(): void
    {
        $crmSegment = $this->createCrmSegmentWithCustomers(3);

        $this->actingAs($this->user)->post(route('marketing.campaigns.store'), [
            'name' => 'Spring blast',
            'channel' => 'email',
            'subject' => 'Hello',
            'customer_segment_id' => $crmSegment->id,
            'audience_count' => 50,
        ])->assertRedirect();

        $campaign = Campaign::query()->where('name', 'Spring blast')->first();
        $this->assertNotNull($campaign);
        $this->assertSame($crmSegment->id, $campaign->customer_segment_id);
        $this->assertSame(3, $campaign->audience_count);

        $this->actingAs($this->user)
            ->get(route('marketing.campaigns.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Marketing/Campaigns/Index', false)
                ->where('campaigns.data.0.audience_count', 3)
                ->where('campaigns.data.0.customer_segment_name', $crmSegment->name)
            );
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
            ->assertInertia(fn ($page) => $page->component('Marketing/Referrals/Index', false));

        $this->actingAs($this->user)->get(route('marketing.reports'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Marketing/Reports/Index', false)
                ->where('stats.referrals', 1)
            );
    }

    private function createCrmSegmentWithCustomers(int $count): CustomerSegment
    {
        $segment = CustomerSegment::query()->create([
            'name' => 'VIP Buyers',
            'code' => 'VIP',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customerIds = [];
        for ($i = 1; $i <= $count; $i++) {
            $customer = Customer::query()->create([
                'code' => sprintf('CUS-MKT-%02d', $i),
                'name' => "Customer {$i}",
                'email' => "customer{$i}@example.com",
                'is_active' => true,
            ]);
            $customerIds[] = $customer->id;
        }

        $segment->customers()->sync($customerIds);

        return $segment->fresh();
    }
}
