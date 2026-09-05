<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Models\NotificationTemplate;

class NotificationsSeeder extends Seeder
{
    public function run(): void
    {
        NotificationTemplate::query()->firstOrCreate(
            ['name' => 'Order confirmed'],
            [
                'channel' => 'email',
                'subject' => 'Your Budget & Bazar order {{number}} is confirmed',
                'body' => 'Hi {{customer_name}}, we received your order and will pack it from MAIN warehouse.',
                'is_active' => true,
            ],
        );

        NotificationTemplate::query()->firstOrCreate(
            ['name' => 'New lead assigned'],
            [
                'channel' => 'email',
                'subject' => 'Lead {{lead_name}} is assigned to you',
                'body' => 'Follow up from {{source}} and move the lead through the CRM pipeline.',
                'is_active' => true,
            ],
        );
    }
}
