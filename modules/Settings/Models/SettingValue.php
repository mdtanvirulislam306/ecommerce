<?php

namespace Modules\Settings\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SettingValue extends Model
{
    use BelongsToTenant;

    protected $fillable = ['group', 'key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_value:{$key}", 300, function () use ($key, $default) {
            $row = static::query()->where('key', $key)->first();

            if ($row === null) {
                return $default;
            }

            $decoded = json_decode((string) $row->value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->value;
        });
    }

    public static function setValue(string $key, mixed $value, string $group = 'general'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => is_string($value) ? $value : json_encode($value)],
        );
        Cache::forget("setting_value:{$key}");
    }

    public static function groupValues(string $group): array
    {
        return static::query()->where('group', $group)->pluck('value', 'key')->map(function ($value) {
            $decoded = json_decode((string) $value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        })->all();
    }
}
