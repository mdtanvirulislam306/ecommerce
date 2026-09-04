<?php

namespace Modules\Workflow\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Workflow\Models\ScheduledTask;

class ScheduledTaskService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return ScheduledTask::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('handler', 'like', "%{$search}%");
                $inner->orWhere('cron', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ScheduledTask $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): ScheduledTask
    {
        return ScheduledTask::query()->create([
            'name' => $data['name'] ?? null,
            'cron' => $data['cron'] ?? '0 * * * *',
            'handler' => $data['handler'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'last_run_at' => $data['last_run_at'] ?? null,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(ScheduledTask $row, array $data): ScheduledTask
    {
        $row->update([
            'name' => array_key_exists('name', $data) ? $data['name'] : $row->name,
            'cron' => array_key_exists('cron', $data) ? $data['cron'] : $row->cron,
            'handler' => array_key_exists('handler', $data) ? $data['handler'] : $row->handler,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $row->is_active,
            'last_run_at' => array_key_exists('last_run_at', $data) ? $data['last_run_at'] : $row->last_run_at,
        ]);

        return $row->fresh();
    }

    public function delete(ScheduledTask $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(ScheduledTask $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'cron' => $row->cron,
            'handler' => $row->handler,
            'is_active' => $row->is_active,
            'last_run_at' => $row->last_run_at?->toIso8601String(),
        ];
    }
}
