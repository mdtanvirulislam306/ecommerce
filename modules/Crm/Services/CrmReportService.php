<?php

namespace Modules\Crm\Services;

use App\Core\Support\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Crm\Enums\LeadStage;
use Modules\Crm\Models\CrmActivity;
use Modules\Crm\Models\Customer;
use Modules\Crm\Models\Lead;
use Modules\Crm\Models\LeadSource;

class CrmReportService extends Service
{
    /**
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $leadQuery = $this->leadsInPeriod($dateFrom, $dateTo);

        $stageCounts = (clone $leadQuery)
            ->selectRaw('stage, COUNT(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->map(fn ($total) => (int) $total)
            ->all();

        $created = (int) (clone $leadQuery)->count();
        $won = (int) ($stageCounts[LeadStage::Won->value] ?? 0);
        $lost = (int) ($stageCounts[LeadStage::Lost->value] ?? 0);
        $converted = (int) (clone $leadQuery)->whereNotNull('converted_customer_id')->count();
        $closed = $won + $lost;
        $openPipeline = $created - $won - $lost;

        $funnel = $this->buildFunnel($stageCounts);
        $bySource = $this->sourcePerformance($dateFrom, $dateTo);

        return [
            'filters' => [
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
            ],
            'summary' => [
                'leads' => $created,
                'open_pipeline' => max(0, $openPipeline),
                'won' => $won,
                'lost' => $lost,
                'converted' => $converted,
                'win_rate' => $closed > 0 ? round(($won / $closed) * 100, 1) : null,
                'convert_rate' => $created > 0 ? round(($converted / $created) * 100, 1) : null,
                'customers' => Customer::query()->count(),
                'active_customers' => Customer::query()->where('is_active', true)->count(),
                'sources' => LeadSource::query()->where('is_active', true)->count(),
                'open_activities' => CrmActivity::query()->whereNull('completed_at')->count(),
                'completed_activities' => CrmActivity::query()->whereNotNull('completed_at')->count(),
            ],
            'funnel' => $funnel,
            'by_stage' => collect(LeadStage::cases())
                ->mapWithKeys(fn (LeadStage $stage) => [
                    $stage->value => (int) ($stageCounts[$stage->value] ?? 0),
                ])
                ->all(),
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

    /**
     * Cumulative funnel from current stages (no stage-history table).
     *
     * @param  array<string, int>  $stageCounts
     * @return list<array{
     *     stage: string,
     *     label: string,
     *     in_stage: int,
     *     reached: int,
     *     conversion_from_previous: float|null,
     *     share_of_top: float|null
     * }>
     */
    private function buildFunnel(array $stageCounts): array
    {
        $stages = LeadStage::pipelineStages();
        $funnel = [];
        $previousReached = null;

        foreach ($stages as $index => $stage) {
            $reached = 0;

            foreach (array_slice($stages, $index) as $later) {
                $reached += (int) ($stageCounts[$later->value] ?? 0);
            }

            $inStage = (int) ($stageCounts[$stage->value] ?? 0);
            $conversion = null;

            if ($index === 0) {
                $conversion = $reached > 0 ? 100.0 : null;
            } elseif ($previousReached !== null && $previousReached > 0) {
                $conversion = round(($reached / $previousReached) * 100, 1);
            } elseif ($previousReached === 0) {
                $conversion = null;
            }

            $top = (int) ($funnel[0]['reached'] ?? $reached);

            $funnel[] = [
                'stage' => $stage->value,
                'label' => $stage->label(),
                'in_stage' => $inStage,
                'reached' => $reached,
                'conversion_from_previous' => $conversion,
                'share_of_top' => $top > 0 ? round(($reached / $top) * 100, 1) : null,
            ];

            $previousReached = $reached;
        }

        return $funnel;
    }

    /**
     * @return list<array{source: string, total: int, won: int, lost: int, win_rate: float|null}>
     */
    private function sourcePerformance(?string $dateFrom, ?string $dateTo): array
    {
        return DB::table('leads')
            ->leftJoin('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
            ->when($dateFrom, fn ($query) => $query->whereDate('leads.created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('leads.created_at', '<=', $dateTo))
            ->selectRaw("COALESCE(lead_sources.name, leads.source, 'Unspecified') as source_name")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN leads.stage = ? THEN 1 ELSE 0 END) as won', [LeadStage::Won->value])
            ->selectRaw('SUM(CASE WHEN leads.stage = ? THEN 1 ELSE 0 END) as lost', [LeadStage::Lost->value])
            ->groupBy('source_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $won = (int) $row->won;
                $lost = (int) $row->lost;
                $closed = $won + $lost;

                return [
                    'source' => $row->source_name,
                    'total' => (int) $row->total,
                    'won' => $won,
                    'lost' => $lost,
                    'win_rate' => $closed > 0 ? round(($won / $closed) * 100, 1) : null,
                ];
            })
            ->all();
    }

    private function leadsInPeriod(?string $dateFrom, ?string $dateTo): Builder
    {
        return Lead::query()
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('created_at', '<=', $dateTo));
    }
}
