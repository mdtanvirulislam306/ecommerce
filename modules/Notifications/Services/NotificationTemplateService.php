<?php

namespace Modules\Notifications\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Notifications\Models\NotificationTemplate;

class NotificationTemplateService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return NotificationTemplate::query()
            ->when($filters['assigned_to'] ?? null, fn ($q, $id) => $q->where('assigned_to', $id))
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('channel', 'like', "%{$search}%");
                $inner->orWhere('subject', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (NotificationTemplate $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): NotificationTemplate
    {
        return NotificationTemplate::query()->create([
            'name' => $data['name'] ?? null,
            'channel' => $data['channel'] ?? 'email',
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(NotificationTemplate $row, array $data): NotificationTemplate
    {
        $row->update([
            'name' => array_key_exists('name', $data) ? $data['name'] : $row->name,
            'channel' => array_key_exists('channel', $data) ? $data['channel'] : $row->channel,
            'subject' => array_key_exists('subject', $data) ? $data['subject'] : $row->subject,
            'body' => array_key_exists('body', $data) ? $data['body'] : $row->body,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $row->is_active,
        ]);

        return $row->fresh();
    }

    public function delete(NotificationTemplate $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(NotificationTemplate $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'channel' => $row->channel,
            'subject' => $row->subject,
            'body' => $row->body,
            'is_active' => $row->is_active,
        ];
    }
}
