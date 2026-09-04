<?php

namespace Modules\Workflow\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Workflow\Models\WorkflowDefinition;

class WorkflowDefinitionService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return WorkflowDefinition::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('trigger', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (WorkflowDefinition $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): WorkflowDefinition
    {
        return WorkflowDefinition::query()->create([
            'name' => $data['name'] ?? null,
            'trigger' => $data['trigger'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'definition' => is_string($data['definition'] ?? null) ? json_decode($data['definition'], true) : ($data['definition'] ?? []),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(WorkflowDefinition $row, array $data): WorkflowDefinition
    {
        $row->update([
            'name' => array_key_exists('name', $data) ? $data['name'] : $row->name,
            'trigger' => array_key_exists('trigger', $data) ? $data['trigger'] : $row->trigger,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $row->is_active,
            'definition' => array_key_exists('definition', $data) ? $data['definition'] : $row->definition,
        ]);

        return $row->fresh();
    }

    public function delete(WorkflowDefinition $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(WorkflowDefinition $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'trigger' => $row->trigger,
            'is_active' => $row->is_active,
            'definition' => $row->definition,
            'definition_json' => json_encode($row->definition ?? []),
        ];
    }
}
