<?php

namespace Modules\Crm\Services;

use App\Core\Support\Service;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Modules\Crm\Enums\ActivityType;
use Modules\Crm\Models\CrmActivity;

class ActivityService extends Service
{
    /**
     * @param  array{
     *     search?: string|null,
     *     type?: ActivityType|null,
     *     status?: string|null,
     *     follow_ups?: bool,
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
        $sort = in_array($filters['sort'] ?? '', ['subject', 'type', 'due_at', 'created_at'], true)
            ? $filters['sort']
            : 'due_at';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $status = $filters['status'] ?? '';

        $query = CrmActivity::query()
            ->with(['lead:id,name', 'customer:id,name,code'])
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type->value))
            ->when($filters['follow_ups'] ?? false, fn ($query) => $query
                ->whereNull('completed_at')
                ->whereNotNull('due_at'))
            ->when($status === 'open', fn ($query) => $query->whereNull('completed_at'))
            ->when($status === 'done', fn ($query) => $query->whereNotNull('completed_at'))
            ->when($status === 'overdue', fn ($query) => $query
                ->whereNull('completed_at')
                ->whereNotNull('due_at')
                ->where('due_at', '<', now()))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('subject', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            }));

        if ($sort === 'due_at' && ! in_array($status, ['done'], true)) {
            $query->orderByRaw('CASE WHEN completed_at IS NULL AND due_at IS NOT NULL THEN 0 ELSE 1 END')
                ->orderBy('due_at', $direction)
                ->orderByDesc('created_at');
        } else {
            $query->orderBy($sort, $direction);
        }

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (CrmActivity $activity) => $this->format($activity));
    }

    /**
     * @param  array{
     *     type: string,
     *     subject: string,
     *     body?: string|null,
     *     due_at?: string|null,
     *     lead_id?: int|null,
     *     customer_id?: int|null
     * }  $data
     */
    public function create(array $data, ?int $userId = null): CrmActivity
    {
        if (empty($data['lead_id']) && empty($data['customer_id'])) {
            throw ValidationException::withMessages([
                'lead_id' => 'Attach the activity to a lead or customer.',
            ]);
        }

        $type = ActivityType::tryFrom($data['type'] ?? ActivityType::Note->value) ?? ActivityType::Note;

        return CrmActivity::query()->create([
            'type' => $type,
            'subject' => $data['subject'],
            'body' => $data['body'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'created_by' => $userId,
        ]);
    }

    public function complete(CrmActivity $activity): CrmActivity
    {
        $activity->update(['completed_at' => now()]);

        return $activity->fresh();
    }

    /**
     * @return array{date: string, days: list<array{date: string, label: string, activities: list<array<string, mixed>>}>}
     */
    public function calendar(?string $date = null): array
    {
        $focus = $date ? Carbon::parse($date) : now();
        $start = $focus->copy()->startOfWeek();
        $end = $focus->copy()->endOfWeek();

        $activities = CrmActivity::query()
            ->with(['lead:id,name', 'customer:id,name'])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->orderBy('due_at')
            ->get()
            ->groupBy(fn (CrmActivity $activity) => $activity->due_at->toDateString());

        $days = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $days[] = [
                'date' => $key,
                'label' => $day->format('D M j'),
                'is_today' => $key === now()->toDateString(),
                'activities' => collect($activities->get($key, []))
                    ->map(fn (CrmActivity $activity) => $this->format($activity))
                    ->values()
                    ->all(),
            ];
        }

        return [
            'date' => $focus->toDateString(),
            'today' => now()->toDateString(),
            'week_label' => $start->format('M j').' – '.$end->format('M j, Y'),
            'prev' => $focus->copy()->subWeek()->toDateString(),
            'next' => $focus->copy()->addWeek()->toDateString(),
            'days' => $days,
        ];
    }

    public function delete(CrmActivity $activity): void
    {
        $activity->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(CrmActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'type' => $activity->type->value,
            'type_label' => $activity->type->label(),
            'subject' => $activity->subject,
            'body' => $activity->body,
            'due_at' => $activity->due_at?->toIso8601String(),
            'completed_at' => $activity->completed_at?->toIso8601String(),
            'is_overdue' => $activity->completed_at === null
                && $activity->due_at !== null
                && $activity->due_at->isPast(),
            'lead_id' => $activity->lead_id,
            'lead_name' => $activity->lead?->name,
            'customer_id' => $activity->customer_id,
            'customer_name' => $activity->customer?->name,
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function typeOptions(): array
    {
        return collect(ActivityType::cases())->map(fn (ActivityType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->all();
    }
}
