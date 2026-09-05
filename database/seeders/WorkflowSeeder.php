<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Workflow\Models\ApprovalPolicy;
use Modules\Workflow\Models\AutomationRule;
use Modules\Workflow\Models\WorkflowDefinition;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        WorkflowDefinition::query()->firstOrCreate(
            ['name' => 'Large sales order approval'],
            [
                'trigger' => 'sales_order.created',
                'is_active' => true,
                'definition' => [
                    'condition' => ['grand_total_gte' => 50000],
                    'action' => 'request_approval',
                ],
            ],
        );

        ApprovalPolicy::query()->firstOrCreate(
            ['name' => 'Large order approval'],
            [
                'entity_type' => 'sales_order',
                'is_active' => true,
                'steps' => [
                    ['name' => 'Manager review', 'role' => 'admin'],
                ],
            ],
        );

        AutomationRule::query()->firstOrCreate(
            ['name' => 'Create task when a lead is created'],
            [
                'event' => 'lead.created',
                'is_active' => true,
                'conditions' => ['stage' => 'new'],
                'actions' => [
                    ['type' => 'create_task', 'title' => 'First contact'],
                ],
            ],
        );
    }
}
