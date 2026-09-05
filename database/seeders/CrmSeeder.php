<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Crm\Enums\LeadStage;
use Modules\Crm\Models\Customer;
use Modules\Crm\Models\Lead;
use Modules\Crm\Models\LeadSource;
use Modules\Crm\Services\ActivityService;
use Modules\Crm\Services\CustomerSegmentService;
use Modules\Crm\Services\CustomerService;
use Modules\Crm\Services\LeadService;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@admin.com')->value('id');
        $retailId = DB::table('customer_groups')->where('code', 'retail')->value('id');
        $wholesaleId = DB::table('customer_groups')->where('code', 'wholesale')->value('id');

        $referral = LeadSource::query()->firstOrCreate(
            ['code' => 'REFERRAL'],
            ['name' => 'Referral', 'is_active' => true, 'sort_order' => 1],
        );
        $facebook = LeadSource::query()->firstOrCreate(
            ['code' => 'FACEBOOK'],
            ['name' => 'Facebook', 'is_active' => true, 'sort_order' => 2],
        );
        LeadSource::query()->firstOrCreate(
            ['code' => 'WALKIN'],
            ['name' => 'Walk-in', 'is_active' => true, 'sort_order' => 3],
        );

        $customers = app(CustomerService::class);
        $leads = app(LeadService::class);

        if (! Customer::query()->where('code', 'CUS-00001')->exists()) {
            $customers->create([
                'name' => 'Walk-in Retail',
                'code' => 'CUS-00001',
                'email' => 'walkin@example.com',
                'phone' => '01700000001',
                'customer_group_id' => $retailId,
                'is_active' => true,
                'notes' => 'Counter / POS walk-in buyer',
            ], $adminId);
        }

        $acme = Lead::query()->where('email', 'buyer@acme.example')->first();

        if ($acme === null) {
            $acme = $leads->create([
                'name' => 'Acme Traders',
                'email' => 'buyer@acme.example',
                'phone' => '01811112222',
                'company' => 'Acme Traders Ltd',
                'source_id' => $referral->id,
                'source' => 'Referral',
                'stage' => LeadStage::Qualified->value,
                'customer_group_id' => $retailId,
                'assigned_to' => $adminId,
                'notes' => 'Interested in wholesale grocery pricing',
            ], $adminId);
        }

        if ($acme->converted_customer_id === null) {
            if (! $acme->stage->canConvert() && $acme->stage->canTransitionTo(LeadStage::Qualified)) {
                $acme = $leads->moveStage($acme, LeadStage::Qualified);
            }

            if ($acme->stage->canConvert()) {
                $leads->convert($acme, $adminId);
                $acme = $acme->fresh();
            }
        }

        if (! Customer::query()->where('code', 'CUS-00003')->exists()) {
            $customers->create([
                'name' => 'Bengal Wholesale',
                'code' => 'CUS-00003',
                'email' => 'orders@bengalwholesale.example',
                'phone' => '01720003333',
                'company' => 'Bengal Wholesale Ltd',
                'address' => 'Kawran Bazar, Dhaka',
                'customer_group_id' => $wholesaleId,
                'is_active' => true,
                'notes' => 'Regular bulk grocery buyer',
            ], $adminId);
        }

        if (! Lead::query()->where('email', 'taufiq@skillbuilders.example')->exists()) {
            $leads->create([
                'name' => 'Taufiq Rahman',
                'email' => 'taufiq@skillbuilders.example',
                'phone' => '01610004444',
                'company' => 'Skill Builders IT',
                'source_id' => $facebook->id,
                'source' => 'Facebook',
                'stage' => LeadStage::Contacted->value,
                'customer_group_id' => $retailId,
                'assigned_to' => $adminId,
                'notes' => 'Asked about office tea and snacks supply',
            ], $adminId);
        }

        $activities = app(ActivityService::class);

        if ($acme && ! DB::table('crm_activities')->where('subject', 'Intro call scheduled')->exists()) {
            $activities->create([
                'type' => 'call',
                'subject' => 'Intro call scheduled',
                'body' => 'Discuss volume pricing and delivery',
                'due_at' => now()->addDay()->toDateTimeString(),
                'lead_id' => $acme->id,
                'customer_id' => $acme->converted_customer_id,
            ], $adminId);
        }

        $openLead = Lead::query()->where('email', 'taufiq@skillbuilders.example')->first();

        if ($openLead && ! DB::table('crm_activities')->where('subject', 'Send price list')->exists()) {
            $activities->create([
                'type' => 'task',
                'subject' => 'Send price list',
                'body' => 'Email retail vs wholesale tiers for tea and oil.',
                'due_at' => now()->addHours(6)->toDateTimeString(),
                'lead_id' => $openLead->id,
            ], $adminId);
        }

        $segmentCustomers = Customer::query()
            ->whereIn('code', ['CUS-00001', 'CUS-00003'])
            ->orWhere('email', 'buyer@acme.example')
            ->pluck('id')
            ->all();

        if ($segmentCustomers !== [] && ! DB::table('customer_segments')->where('code', 'ACTIVE-BUYERS')->exists()) {
            app(CustomerSegmentService::class)->create([
                'name' => 'Active buyers',
                'code' => 'ACTIVE-BUYERS',
                'description' => 'Customers used for sales, POS, and campaigns',
                'is_active' => true,
                'sort_order' => 1,
                'customer_ids' => $segmentCustomers,
            ]);
        }
    }
}
