<?php

namespace Modules\Settings\Services;

use App\Core\Support\Service;
use Modules\Settings\Models\SettingValue;

class SettingService extends Service
{
    public function getGroup(string $group): array
    {
        return SettingValue::groupValues($group);
    }

    /** @param  array<string, mixed>  $data */
    public function saveGroup(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            SettingValue::setValue((string) $key, $value, $group);
        }
    }
}
