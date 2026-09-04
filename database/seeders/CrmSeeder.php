<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Crm\Services\ActivityService;
use Modules\Crm\Services\CustomerService;
use Modules\Crm\Services\LeadService;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $groupId = DB::table('customer_groups')->where('code', 'retail')->value('id')
            ?? DB::table('customer_groups')->orderBy('id')->value('id');

        if (! DB::table('customers')->exists()) {
            app(CustomerService::class)->create([
                'name' => 'Walk-in Retail',
                'code' => 'CUS-00001',
                'email' => 'walkin@example.com',
                'phone' => '01700000001',
                'customer_group_id' => $groupId,
                'is_active' => true,
                'notes' => 'Default retail customer from seeder',
            ]);
        }

        if (DB::table('leads')->exists()) {
            return;
        }

        $lead = app(LeadService::class)->create([
            'name' => 'Acme Traders',
            'email' => 'buyer@acme.example',
            'phone' => '01811112222',
            'company' => 'Acme Traders Ltd',
            'source' => 'Referral',
            'stage' => 'qualified',
            'customer_group_id' => $groupId,
            'notes' => 'Interested in wholesale pricing',
        ]);

        app(ActivityService::class)->create([
            'type' => 'call',
            'subject' => 'Intro call scheduled',
            'body' => 'Discuss volume pricing and delivery',
            'due_at' => now()->addDay()->toDateTimeString(),
            'lead_id' => $lead->id,
        ]);
    }
}
