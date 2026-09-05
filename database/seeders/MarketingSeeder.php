<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Marketing\Enums\CampaignChannel;
use Modules\Marketing\Enums\DiscountType;
use Modules\Marketing\Models\Campaign;
use Modules\Marketing\Models\MarketingCoupon;
use Modules\Marketing\Models\Segment;
use Modules\Marketing\Services\CampaignService;

class MarketingSeeder extends Seeder
{
    public function run(): void
    {
        $customerCount = (int) DB::table('customers')->count();

        Segment::query()->firstOrCreate(
            ['name' => 'Grocery buyers'],
            [
                'description' => 'Retail and wholesale grocery customers',
                'rules' => ['customer_group' => ['retail', 'wholesale']],
                'customer_count' => $customerCount,
                'is_active' => true,
            ],
        );

        MarketingCoupon::query()->firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'name' => 'Welcome 10%',
                'type' => DiscountType::Percentage,
                'value' => 10,
                'usage_limit' => 200,
                'used_count' => 0,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(3),
                'is_active' => true,
                'description' => 'First-order discount for storefront and POS',
            ],
        );

        if (Campaign::query()->where('name', 'Welcome grocery list')->exists()) {
            return;
        }

        $adminId = User::query()->where('email', 'admin@admin.com')->value('id');

        app(CampaignService::class)->create([
            'name' => 'Welcome grocery list',
            'channel' => CampaignChannel::Email->value,
            'subject' => 'This week at Budget & Bazar',
            'body' => 'Rice, oil, tea, and dal are in stock. Use WELCOME10 on your first web order.',
            'audience_count' => $customerCount,
        ], $adminId);
    }
}
