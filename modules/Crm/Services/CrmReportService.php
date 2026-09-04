<?php

namespace Modules\Crm\Services;

use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Modules\Crm\Models\CrmActivity;
use Modules\Crm\Models\Customer;
use Modules\Crm\Models\Lead;
use Modules\Crm\Models\LeadSource;

class CrmReportService extends Service
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $byStage = Lead::query()
            ->selectRaw('stage, COUNT(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->map(fn ($total) => (int) $total)
            ->all();

        $bySource = DB::table('leads')
            ->leftJoin('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
            ->selectRaw("COALESCE(lead_sources.name, leads.source, 'Unspecified') as source_name, COUNT(*) as total")
            ->groupBy('source_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'source' => $row->source_name,
                'total' => (int) $row->total,
            ])
            ->all();

        return [
            'summary' => [
                'leads' => Lead::query()->count(),
                'customers' => Customer::query()->count(),
                'active_customers' => Customer::query()->where('is_active', true)->count(),
                'sources' => LeadSource::query()->where('is_active', true)->count(),
                'open_activities' => CrmActivity::query()->whereNull('completed_at')->count(),
                'completed_activities' => CrmActivity::query()->whereNotNull('completed_at')->count(),
            ],
            'by_stage' => $byStage,
            'by_source' => $bySource,
            'recent_activities' => CrmActivity::query()
                ->with(['lead:id,name', 'customer:id,name'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn (CrmActivity $activity) => [
                    'id' => $activity->id,
                    'subject' => $activity->subject,
                    'type' => $activity->type->value ?? $activity->type,
                    'related' => $activity->lead?->name ?? $activity->customer?->name,
                    'due_at' => $activity->due_at?->toIso8601String(),
                    'completed_at' => $activity->completed_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }
}
