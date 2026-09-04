<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ShopSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        // Cache the decoded scalar/array — never the Eloquent model (file/redis
        // unserialize can yield "__PHP_Incomplete_Class" before the module autoloads).
        return Cache::remember("shop_setting:{$key}", 300, function () use ($key, $default) {
            $row = static::query()->where('key', $key)->first();

            if ($row === null) {
                return $default;
            }

            $decoded = json_decode((string) $row->value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->value;
        });
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_string($value) ? $value : json_encode($value)],
        );

        Cache::forget("shop_setting:{$key}");
    }

    public static function flag(string $key, bool $default = false): bool
    {
        return (bool) static::getValue($key, $default);
    }
}
