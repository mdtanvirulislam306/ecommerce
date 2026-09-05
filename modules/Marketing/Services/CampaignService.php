<?php

namespace Modules\Marketing\Services;

use App\Core\Module\ModuleManager;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Modules\Crm\Models\CustomerSegment;
use Modules\Crm\Services\CustomerSegmentService;
use Modules\Marketing\Enums\CampaignChannel;
use Modules\Marketing\Enums\CampaignStatus;
use Modules\Marketing\Enums\DiscountType;
use Modules\Marketing\Enums\ReferralStatus;
use Modules\Marketing\Models\Campaign;
use Modules\Marketing\Models\LoyaltySetting;
use Modules\Marketing\Models\MarketingCoupon;
use Modules\Marketing\Models\MarketingPromotion;
use Modules\Marketing\Models\Referral;
use Modules\Marketing\Models\Segment;
use Modules\Marketing\Models\Story;

class CampaignService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25, ?string $channel = null): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Campaign::query()
            ->with(['customerSegment' => fn ($query) => $query->withCount('customers')])
            ->when($channel, fn ($query) => $query->where('channel', $channel))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Campaign $campaign) => $this->format($campaign));
    }

    /**
     * @return array<string, mixed>
     */
    public function overviewStats(): array
    {
        $campaignsByChannel = [];

        foreach (CampaignChannel::cases() as $channel) {
            $campaignsByChannel[$channel->value] = Campaign::query()
                ->where('channel', $channel->value)
                ->count();
        }

        return [
            'stories' => Story::query()->count(),
            'stories_active' => Story::query()->where('is_active', true)->count(),
            'campaigns' => Campaign::query()->count(),
            'campaigns_by_channel' => $campaignsByChannel,
            'campaigns_draft' => Campaign::query()->where('status', CampaignStatus::Draft)->count(),
            'campaigns_sent' => Campaign::query()->where('status', CampaignStatus::Sent)->count(),
            'segments' => Segment::query()->count(),
            'segments_active' => Segment::query()->where('is_active', true)->count(),
            'promotions' => MarketingPromotion::query()->count(),
            'promotions_active' => MarketingPromotion::query()->where('is_active', true)->count(),
            'coupons' => MarketingCoupon::query()->count(),
            'coupons_active' => MarketingCoupon::query()->where('is_active', true)->count(),
            'loyalty_programs' => LoyaltySetting::query()->count(),
            'loyalty_active' => LoyaltySetting::query()->where('is_active', true)->count(),
            'referrals' => Referral::query()->count(),
            'referrals_pending' => Referral::query()->where('status', ReferralStatus::Pending)->count(),
            'referrals_completed' => Referral::query()->where('status', ReferralStatus::Completed)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reportStats(): array
    {
        $overview = $this->overviewStats();

        return array_merge($overview, [
            'campaigns_scheduled' => Campaign::query()->where('status', CampaignStatus::Scheduled)->count(),
            'campaigns_cancelled' => Campaign::query()->where('status', CampaignStatus::Cancelled)->count(),
            'total_audience' => (int) Campaign::query()->sum('audience_count'),
            'coupons_used' => (int) MarketingCoupon::query()->sum('used_count'),
            'referral_rewards' => (float) Referral::query()
                ->where('status', ReferralStatus::Completed)
                ->sum('reward_amount'),
            'discount_types' => collect(DiscountType::cases())->mapWithKeys(fn (DiscountType $type) => [
                $type->value => MarketingPromotion::query()->where('type', $type->value)->count(),
            ])->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId = null): Campaign
    {
        [$customerSegmentId, $audienceCount] = $this->resolveAudience($data);

        return Campaign::query()->create([
            'name' => $data['name'],
            'channel' => $data['channel'] ?? CampaignChannel::Email->value,
            'status' => CampaignStatus::Draft,
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'audience_count' => $audienceCount,
            'customer_segment_id' => $customerSegmentId,
            'created_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Campaign $campaign, array $data): Campaign
    {
        [$customerSegmentId, $audienceCount] = $this->resolveAudience(array_merge([
            'customer_segment_id' => $campaign->customer_segment_id,
            'audience_count' => $campaign->audience_count,
        ], $data));

        $campaign->update([
            'name' => $data['name'] ?? $campaign->name,
            'channel' => $data['channel'] ?? $campaign->channel,
            'status' => $data['status'] ?? $campaign->status,
            'subject' => $data['subject'] ?? $campaign->subject,
            'body' => $data['body'] ?? $campaign->body,
            'scheduled_at' => $data['scheduled_at'] ?? $campaign->scheduled_at,
            'audience_count' => $audienceCount,
            'customer_segment_id' => $customerSegmentId,
        ]);

        return $campaign->fresh(['customerSegment']);
    }

    public function markSent(Campaign $campaign): Campaign
    {
        $campaign->update([
            'status' => CampaignStatus::Sent,
            'sent_at' => now(),
        ]);

        return $campaign->fresh();
    }

    public function delete(Campaign $campaign): void
    {
        $campaign->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Campaign $campaign): array
    {
        $crm = $campaign->customerSegment;
        $audience = $crm !== null
            ? (int) ($crm->customers_count ?? $crm->customers()->count())
            : (int) $campaign->audience_count;

        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'channel' => $campaign->channel?->value,
            'channel_label' => $campaign->channel?->label(),
            'status' => $campaign->status?->value,
            'status_label' => $campaign->status?->label(),
            'subject' => $campaign->subject,
            'body' => $campaign->body,
            'scheduled_at' => $campaign->scheduled_at?->toIso8601String(),
            'sent_at' => $campaign->sent_at?->toIso8601String(),
            'audience_count' => $audience,
            'customer_segment_id' => $campaign->customer_segment_id,
            'customer_segment_name' => $crm?->name,
            'created_at' => $campaign->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, code: string, customers_count: int}>
     */
    public function crmSegmentOptions(): array
    {
        if (! app(ModuleManager::class)->enabled('crm') || ! Schema::hasTable('customer_segments')) {
            return [];
        }

        return app(CustomerSegmentService::class)->audienceOptions();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: int|null, 1: int}
     */
    private function resolveAudience(array $data): array
    {
        $customerSegmentId = array_key_exists('customer_segment_id', $data)
            ? (! empty($data['customer_segment_id']) ? (int) $data['customer_segment_id'] : null)
            : null;
        $audienceCount = (int) ($data['audience_count'] ?? 0);

        if ($customerSegmentId
            && app(ModuleManager::class)->enabled('crm')
            && Schema::hasTable('customer_segments')
        ) {
            $crm = CustomerSegment::query()->withCount('customers')->find($customerSegmentId);
            if ($crm) {
                return [$crm->id, (int) $crm->customers_count];
            }
        }

        return [null, $audienceCount];
    }
}
