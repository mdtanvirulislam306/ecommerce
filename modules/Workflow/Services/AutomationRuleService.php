<?php

namespace Modules\Workflow\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Workflow\Models\AutomationRule;

class AutomationRuleService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return AutomationRule::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('event', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (AutomationRule $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): AutomationRule
    {
        return AutomationRule::query()->create([
            'name' => $data['name'] ?? null,
            'event' => $data['event'] ?? null,
            'conditions' => is_string($data['conditions'] ?? null) ? json_decode($data['conditions'], true) : ($data['conditions'] ?? []),
            'actions' => is_string($data['actions'] ?? null) ? json_decode($data['actions'], true) : ($data['actions'] ?? []),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(AutomationRule $row, array $data): AutomationRule
    {
        $row->update([
            'name' => array_key_exists('name', $data) ? $data['name'] : $row->name,
            'event' => array_key_exists('event', $data) ? $data['event'] : $row->event,
            'conditions' => array_key_exists('conditions', $data) ? $data['conditions'] : $row->conditions,
            'actions' => array_key_exists('actions', $data) ? $data['actions'] : $row->actions,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $row->is_active,
        ]);

        return $row->fresh();
    }

    public function delete(AutomationRule $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(AutomationRule $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'event' => $row->event,
            'conditions' => $row->conditions,
            'actions' => $row->actions,
            'is_active' => $row->is_active,
            'conditions_json' => json_encode($row->conditions ?? []),
            'actions_json' => json_encode($row->actions ?? []),
        ];
    }
}
