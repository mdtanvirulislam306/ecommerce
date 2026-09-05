<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Support\Enums\TicketPriority;
use Modules\Support\Models\CannedResponse;
use Modules\Support\Models\SupportCategory;
use Modules\Support\Services\TicketService;

class SupportSeeder extends Seeder
{
    public function run(): void
    {
        $orders = SupportCategory::query()->firstOrCreate(
            ['slug' => 'orders'],
            ['name' => 'Orders', 'is_active' => true],
        );

        SupportCategory::query()->firstOrCreate(
            ['slug' => 'billing'],
            ['name' => 'Billing', 'is_active' => true],
        );

        CannedResponse::query()->firstOrCreate(
            ['title' => 'Order received'],
            [
                'body' => 'Thanks for reaching out. We have your order and will update you once it is packed.',
                'category_id' => $orders->id,
                'is_active' => true,
            ],
        );

        if (DB::table('support_tickets')->where('requester_email', 'buyer@acme.example')->exists()) {
            return;
        }

        $adminId = User::query()->where('email', 'admin@admin.com')->value('id');

        app(TicketService::class)->create([
            'subject' => 'Need delivery window for tea order',
            'body' => 'Acme Traders asked when the pending sales order of Premium Tea can be delivered to Motijheel.',
            'priority' => TicketPriority::Normal->value,
            'requester_name' => 'Acme Traders',
            'requester_email' => 'buyer@acme.example',
        ], $adminId);
    }
}
