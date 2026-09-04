<?php

namespace Modules\Crm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Crm\Enums\LeadStage;
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

    public function listPaginated(
        ?string $search = null,
        ?LeadStage $stage = null,
        ?int $assignedTo = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Lead::query()
            ->with(['leadSource:id,name'])
            ->withCount('activities')
            ->when($stage, fn ($query, $stage) => $query->where('stage', $stage->value))
            ->when($assignedTo, fn ($query, $assignedTo) => $query->where('assigned_to', $assignedTo))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Lead $lead) => $this->format($lead));
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function pipelineBoard(): array
    {
        $leads = Lead::query()
            ->whereIn('stage', array_map(fn (LeadStage $s) => $s->value, LeadStage::pipelineStages()))
            ->orderByDesc('updated_at')
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
            'converted_customer_id' => $lead->converted_customer_id,
            'converted_at' => $lead->converted_at?->toIso8601String(),
            'can_convert' => $lead->stage->canConvert() && ! $lead->converted_customer_id,
            'activities_count' => $lead->activities_count ?? $lead->activities()->count(),
            'created_at' => $lead->created_at?->toIso8601String(),
        ];
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
