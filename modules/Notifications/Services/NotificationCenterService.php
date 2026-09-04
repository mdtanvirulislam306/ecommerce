<?php

namespace Modules\Notifications\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Notifications\Models\UserNotification;

class NotificationCenterService extends Service
{
    public function listForUser(int $userId, int $perPage = 25): LengthAwarePaginator
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (UserNotification $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'channel' => $n->channel,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
            ]);
    }

    public function markRead(UserNotification $notification): void
    {
        $notification->update(['read_at' => now()]);
    }
}
