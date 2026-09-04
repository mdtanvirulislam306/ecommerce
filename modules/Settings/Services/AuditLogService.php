<?php

namespace Modules\Settings\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Settings\Models\AuditLog;

class AuditLogService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return AuditLog::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('action', 'like', "%{$search}%");
                $inner->orWhere('subject_type', 'like', "%{$search}%");
                $inner->orWhere('ip_address', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (AuditLog $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): AuditLog
    {
        return AuditLog::query()->create([
            'user_id' => $data['user_id'] ?? null,
            'action' => $data['action'] ?? null,
            'subject_type' => $data['subject_type'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'properties' => $data['properties'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(AuditLog $row, array $data): AuditLog
    {
        $row->update([
            'user_id' => array_key_exists('user_id', $data) ? $data['user_id'] : $row->user_id,
            'action' => array_key_exists('action', $data) ? $data['action'] : $row->action,
            'subject_type' => array_key_exists('subject_type', $data) ? $data['subject_type'] : $row->subject_type,
            'subject_id' => array_key_exists('subject_id', $data) ? $data['subject_id'] : $row->subject_id,
            'properties' => array_key_exists('properties', $data) ? $data['properties'] : $row->properties,
            'ip_address' => array_key_exists('ip_address', $data) ? $data['ip_address'] : $row->ip_address,
        ]);

        return $row->fresh();
    }

    public function delete(AuditLog $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(AuditLog $row): array
    {
        return [
            'id' => $row->id,
            'user_id' => $row->user_id,
            'action' => $row->action,
            'subject_type' => $row->subject_type,
            'subject_id' => $row->subject_id,
            'properties' => $row->properties,
            'ip_address' => $row->ip_address,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
