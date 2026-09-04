<?php

namespace Modules\Tasks\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Tasks\Models\Task;

class TaskService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Task::query()
            ->when($filters['assigned_to'] ?? null, fn ($q, $id) => $q->where('assigned_to', $id))
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%");
                $inner->orWhere('description', 'like', "%{$search}%");
                $inner->orWhere('status', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Task $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Task
    {
        return Task::query()->create([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'status' => $data['status'] ?? 'open',
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(Task $row, array $data): Task
    {
        $row->update([
            'title' => array_key_exists('title', $data) ? $data['title'] : $row->title,
            'description' => array_key_exists('description', $data) ? $data['description'] : $row->description,
            'due_at' => array_key_exists('due_at', $data) ? $data['due_at'] : $row->due_at,
            'status' => array_key_exists('status', $data) ? $data['status'] : $row->status,
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $row->assigned_to,
            'created_by' => array_key_exists('created_by', $data) ? $data['created_by'] : $row->created_by,
        ]);

        return $row->fresh();
    }

    public function delete(Task $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(Task $row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'description' => $row->description,
            'due_at' => $row->due_at?->toIso8601String(),
            'status' => $row->status,
            'assigned_to' => $row->assigned_to,
            'created_by' => $row->created_by,
        ];
    }
}
