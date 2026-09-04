<?php

namespace Modules\Workflow\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Workflow\Models\ApprovalPolicy;

class ApprovalPolicyService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return ApprovalPolicy::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('entity_type', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ApprovalPolicy $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): ApprovalPolicy
    {
        return ApprovalPolicy::query()->create([
            'name' => $data['name'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'steps' => is_string($data['steps'] ?? null) ? json_decode($data['steps'], true) : ($data['steps'] ?? []),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(ApprovalPolicy $row, array $data): ApprovalPolicy
    {
        $row->update([
            'name' => array_key_exists('name', $data) ? $data['name'] : $row->name,
            'entity_type' => array_key_exists('entity_type', $data) ? $data['entity_type'] : $row->entity_type,
            'steps' => array_key_exists('steps', $data) ? $data['steps'] : $row->steps,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $row->is_active,
        ]);

        return $row->fresh();
    }

    public function delete(ApprovalPolicy $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(ApprovalPolicy $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'entity_type' => $row->entity_type,
            'steps' => $row->steps,
            'is_active' => $row->is_active,
            'steps_json' => json_encode($row->steps ?? []),
        ];
    }
}
