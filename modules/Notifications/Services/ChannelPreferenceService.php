<?php

namespace Modules\Notifications\Services;

use App\Core\Support\Service;
use Modules\Notifications\Models\ChannelPreference;

class ChannelPreferenceService extends Service
{
    public function forUser(int $userId): array
    {
        $channels = ['email', 'sms', 'whatsapp', 'push'];
        $existing = ChannelPreference::query()->where('user_id', $userId)->get()->keyBy('channel');

        return collect($channels)->map(fn ($channel) => [
            'channel' => $channel,
            'enabled' => $existing->get($channel)?->enabled ?? true,
        ])->all();
    }

    /** @param  array<int, array{channel: string, enabled: bool}>  $prefs */
    public function save(int $userId, array $prefs): void
    {
        foreach ($prefs as $pref) {
            ChannelPreference::query()->updateOrCreate(
                ['user_id' => $userId, 'channel' => $pref['channel']],
                ['enabled' => (bool) ($pref['enabled'] ?? true)],
            );
        }
    }
}
