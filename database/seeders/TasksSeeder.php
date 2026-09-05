<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Tasks\Models\Task;

class TasksSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@admin.com')->value('id');

        Task::query()->firstOrCreate(
            ['title' => 'Follow up Acme wholesale order'],
            [
                'description' => 'Confirm delivery date for the pending Premium Tea sales order.',
                'due_at' => now()->addDays(2),
                'status' => 'open',
                'assigned_to' => $adminId,
                'created_by' => $adminId,
            ],
        );

        Task::query()->firstOrCreate(
            ['title' => 'Send Skill Builders price list'],
            [
                'description' => 'Email retail vs wholesale tiers for tea and oil to Taufiq.',
                'due_at' => now()->addDay(),
                'status' => 'open',
                'assigned_to' => $adminId,
                'created_by' => $adminId,
            ],
        );
    }
}
