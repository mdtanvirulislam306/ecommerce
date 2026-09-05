<?php

namespace Modules\Crm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Crm\Enums\LeadStage;
use Modules\Crm\Models\CrmActivity;
use Modules\Crm\Models\Customer;
use Modules\Crm\Models\Lead;

class LeadService extends Service
{
    public function __construct(
        private readonly CustomerService $customers,
    ) {}

    /**
     * @return array{leads: int, customers: int, new: int, pipeline: int, won: int, activities_due: int}
     */
    public function overviewStats(): array
    {
        $stageCounts = Lead::query()
            ->selectRaw('stage, COUNT(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        $pipeline = collect(LeadStage::pipelineStages())
            ->reject(fn (LeadStage $stage) => $stage === LeadStage::Won)
            ->sum(fn (LeadStage $stage) => (int) ($stageCounts[$stage->value] ?? 0));

        return [
            'leads' => Lead::query()->count(),
            'customers' => Customer::query()->where('is_active', true)->count(),
            'new' => (int) ($stageCounts[LeadStage::New->value] ?? 0),
            'pipeline' => $pipeline,
            'won' => (int) ($stageCounts[LeadStage::Won->value] ?? 0),
            'activities_due' => DB::table('crm_activities')
                ->whereNull('completed_at')
                ->whereNotNull('due_at')
                ->where('due_at', '<=', now()->addDay())
                ->count(),
        ];
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     stage?: LeadStage|null,
     *     assigned_to?: int|null,
     *     source_id?: int|null,
     *     converted?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     sort?: string|null,
     *     direction?: string|null,
     *     per_page?: int
     * }  $filters
     */
    public function listPaginated(array $filters = []): LengthAwarePaginator
    {
        $perPage = in_array((int) ($filters['per_page'] ?? 25), [10, 25, 50, 100], true)
            ? (int) $filters['per_page']
            : 25;

        return $this->sortedFilteredQuery($filters)
            ->with(['leadSource:id,name', 'assignedUser:id,name', 'nextAction'])
            ->withCount('activities')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Lead $lead) => $this->format($lead));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function listForExport(array $filters = []): Collection
    {
        return $this->sortedFilteredQuery($filters)
            ->with(['leadSource:id,name', 'assignedUser:id,name'])
            ->limit(2000)
            ->get()
            ->map(fn (Lead $lead) => [
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'stage' => $lead->stage->label(),
                'source' => $lead->leadSource?->name ?? $lead->source,
                'owner' => $lead->assignedUser?->name,
                'converted' => $lead->converted_customer_id ? 'Yes' : 'No',
                'created_at' => $lead->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentLeads(int $limit = 8): array
    {
        return Lead::query()
            ->with(['leadSource:id,name', 'assignedUser:id,name', 'nextAction'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Lead $lead) => $this->format($lead))
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function optionList(int $limit = 50): array
    {
        return Lead::query()
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function assigneeOptions(): array
    {
        return DB::table('users')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }

    /**
     * @param  array{search?: string|null, source_id?: int|null, date_from?: string|null, date_to?: string|null}  $filters
     * @return array<string, list<array<string, mixed>>>
     */
    public function pipelineBoard(array $filters = []): array
    {
        $leads = $this->filteredQuery($filters)
            ->with(['leadSource:id,name', 'assignedUser:id,name'])
            ->whereIn('leads.stage', array_map(fn (LeadStage $s) => $s->value, LeadStage::pipelineStages()))
            ->orderByDesc('leads.updated_at')
            ->limit(200)
            ->get();

        $board = [];

        foreach (LeadStage::pipelineStages() as $stage) {
            $board[$stage->value] = $leads
                ->where('stage', $stage)
                ->values()
                ->map(fn (Lead $lead) => $this->format($lead))
                ->all();
        }

        return $board;
    }

    /**
     * @param  array{
     *     name: string,
     *     email?: string|null,
     *     phone?: string|null,
     *     company?: string|null,
     *     source?: string|null,
     *     stage?: string,
     *     customer_group_id?: int|null,
     *     notes?: string|null,
     *     assigned_to?: int|null
     * }  $data
     */
    public function create(array $data, ?int $userId = null): Lead
    {
        $stage = LeadStage::tryFrom($data['stage'] ?? LeadStage::New->value) ?? LeadStage::New;

        if (in_array($stage, [LeadStage::Won, LeadStage::Lost], true)) {
            throw ValidationException::withMessages([
                'stage' => 'Create the lead as New, then move it through the pipeline.',
            ]);
        }

        return Lead::query()->create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'source' => $data['source'] ?? null,
            'stage' => $stage,
            'customer_group_id' => $data['customer_group_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? $userId,
            'created_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Lead $lead, array $data): Lead
    {
        if (in_array($lead->stage, [LeadStage::Won, LeadStage::Lost], true)) {
            throw ValidationException::withMessages([
                'stage' => 'Closed leads cannot be edited.',
            ]);
        }

        $lead->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'source_id' => array_key_exists('source_id', $data) ? $data['source_id'] : $lead->source_id,
            'source' => $data['source'] ?? null,
            'customer_group_id' => $data['customer_group_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? $lead->assigned_to,
        ]);

        return $lead->fresh();
    }

    public function moveStage(Lead $lead, LeadStage $to): Lead
    {
        if (! $lead->stage->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'stage' => "Cannot move from {$lead->stage->label()} to {$to->label()}.",
            ]);
        }

        $lead->update(['stage' => $to]);

        return $lead->fresh();
    }

    public function convert(Lead $lead, ?int $userId = null): Customer
    {
        return DB::transaction(function () use ($lead, $userId) {
            $lead = Lead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();

            if ($lead->converted_customer_id) {
                throw ValidationException::withMessages([
                    'lead' => 'This lead was already converted.',
                ]);
            }

            if (! $lead->stage->canConvert()) {
                throw ValidationException::withMessages([
                    'stage' => 'Qualify or win the lead before converting to a customer.',
                ]);
            }

            $customer = $this->customers->create([
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'customer_group_id' => $lead->customer_group_id,
                'notes' => $lead->notes,
                'is_active' => true,
            ], $userId);

            $lead->update([
                'stage' => LeadStage::Won,
                'converted_customer_id' => $customer->id,
                'converted_at' => now(),
            ]);

            DB::table('crm_activities')
                ->where('lead_id', $lead->id)
                ->whereNull('customer_id')
                ->update(['customer_id' => $customer->id]);

            return $customer;
        });
    }

    public function delete(Lead $lead): void
    {
        if ($lead->converted_customer_id || $lead->stage === LeadStage::Won) {
            throw ValidationException::withMessages([
                'lead' => 'Cannot delete a converted or won lead.',
            ]);
        }

        $lead->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Lead $lead): array
    {
        $groupName = null;

        if ($lead->customer_group_id) {
            $groupName = DB::table('customer_groups')->where('id', $lead->customer_group_id)->value('name');
        }

        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company,
            'source' => $lead->leadSource?->name ?? $lead->source,
            'source_id' => $lead->source_id,
            'stage' => $lead->stage->value,
            'stage_label' => $lead->stage->label(),
            'customer_group_id' => $lead->customer_group_id,
            'customer_group_name' => $groupName,
            'notes' => $lead->notes,
            'assigned_to' => $lead->assigned_to,
            'assigned_to_name' => $lead->assignedUser?->name,
            'converted_customer_id' => $lead->converted_customer_id,
            'converted_at' => $lead->converted_at?->toIso8601String(),
            'can_convert' => $lead->stage->canConvert() && ! $lead->converted_customer_id,
            'activities_count' => $lead->activities_count ?? $lead->activities()->count(),
            'next_action' => $this->formatNextAction($lead),
            'created_at' => $lead->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{id: int, type: string, type_label: string, subject: string, due_at: string, is_overdue: bool}|null
     */
    private function formatNextAction(Lead $lead): ?array
    {
        $activity = $lead->relationLoaded('nextAction')
            ? $lead->nextAction
            : $lead->nextAction()->first();

        if ($activity === null) {
            return null;
        }

        return [
            'id' => $activity->id,
            'type' => $activity->type->value,
            'type_label' => $activity->type->label(),
            'subject' => $activity->subject,
            'due_at' => $activity->due_at?->toIso8601String(),
            'is_overdue' => $activity->due_at !== null && $activity->due_at->isPast(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $pipelineValues = array_map(fn (LeadStage $stage) => $stage->value, LeadStage::pipelineStages());

        return Lead::query()
            ->select('leads.*')
            ->when(
                ($filters['pipeline_only'] ?? false) && empty($filters['stage']),
                fn ($query) => $query->whereIn('leads.stage', $pipelineValues),
            )
            ->when($filters['stage'] ?? null, fn ($query, $stage) => $query->where('leads.stage', $stage->value))
            ->when($filters['assigned_to'] ?? null, fn ($query, $assignedTo) => $query->where('leads.assigned_to', $assignedTo))
            ->when($filters['source_id'] ?? null, fn ($query, $sourceId) => $query->where('leads.source_id', $sourceId))
            ->when(($filters['converted'] ?? '') === 'yes', fn ($query) => $query->whereNotNull('leads.converted_customer_id'))
            ->when(($filters['converted'] ?? '') === 'no', fn ($query) => $query->whereNull('leads.converted_customer_id'))
            ->when($filters['date_from'] ?? null, fn ($query, $from) => $query->whereDate('leads.created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($query, $to) => $query->whereDate('leads.created_at', '<=', $to))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('leads.name', 'like', "%{$search}%")
                    ->orWhere('leads.email', 'like', "%{$search}%")
                    ->orWhere('leads.phone', 'like', "%{$search}%")
                    ->orWhere('leads.company', 'like', "%{$search}%")
                    ->orWhere('leads.source', 'like', "%{$search}%");
            }));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function pipelineStageCounts(array $filters = []): array
    {
        $filters['pipeline_only'] = true;
        unset($filters['stage']);

        $counts = $this->filteredQuery($filters)
            ->select('leads.stage')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('leads.stage')
            ->pluck('total', 'stage');

        $result = [];

        foreach (LeadStage::pipelineStages() as $stage) {
            $result[$stage->value] = (int) ($counts[$stage->value] ?? 0);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function sortedFilteredQuery(array $filters): Builder
    {
        $sort = in_array($filters['sort'] ?? '', ['name', 'stage', 'source', 'company', 'created_at', 'next_action'], true)
            ? $filters['sort']
            : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query = $this->filteredQuery($filters);

        if ($sort === 'source') {
            return $query->leftJoin('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
                ->orderByRaw('COALESCE(lead_sources.name, leads.source) '.$direction);
        }

        if ($sort === 'next_action') {
            return $query
                ->orderBy(
                    CrmActivity::query()
                        ->select('due_at')
                        ->whereColumn('crm_activities.lead_id', 'leads.id')
                        ->whereNull('completed_at')
                        ->whereNotNull('due_at')
                        ->orderBy('due_at')
                        ->limit(1),
                    $direction,
                )
                ->orderBy('leads.id');
        }

        return $query->orderBy('leads.'.$sort, $direction);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function stageOptions(): array
    {
        return collect(LeadStage::cases())->map(fn (LeadStage $stage) => [
            'value' => $stage->value,
            'label' => $stage->label(),
        ])->all();
    }
}
