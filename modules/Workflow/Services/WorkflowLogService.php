<?php

namespace Modules\Workflow\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Workflow\Models\WorkflowLog;

class WorkflowLogService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return WorkflowLog::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('source', 'like', "%{$search}%");
                $inner->orWhere('message', 'like', "%{$search}%");
                $inner->orWhere('level', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (WorkflowLog $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): WorkflowLog
    {
        return WorkflowLog::query()->create([
            'source' => $data['source'] ?? null,
            'message' => $data['message'] ?? null,
            'level' => $data['level'] ?? 'info',
            'context' => is_string($data['context'] ?? null) ? json_decode($data['context'], true) : ($data['context'] ?? []),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(WorkflowLog $row, array $data): WorkflowLog
    {
        $row->update([
            'source' => array_key_exists('source', $data) ? $data['source'] : $row->source,
            'message' => array_key_exists('message', $data) ? $data['message'] : $row->message,
            'level' => array_key_exists('level', $data) ? $data['level'] : $row->level,
            'context' => array_key_exists('context', $data) ? $data['context'] : $row->context,
        ]);

        return $row->fresh();
    }

    public function delete(WorkflowLog $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(WorkflowLog $row): array
    {
        return [
            'id' => $row->id,
            'source' => $row->source,
            'message' => $row->message,
            'level' => $row->level,
            'context' => $row->context,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
