<?php

namespace Modules\Billing\Models;

use App\Core\Support\BelongsToTenant;
use App\Core\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ShopSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    public static function getValue(string $key, mixed $default = null, ?int $tenantId = null): mixed
    {
        $tenantId ??= app(TenantContext::class)->id();
        $cacheKey = self::cacheKey($key, $tenantId);

        return Cache::remember($cacheKey, 300, function () use ($key, $default, $tenantId) {
            $query = static::query()->where('key', $key);
            if ($tenantId !== null && Schema::hasColumn('shop_settings', 'tenant_id')) {
                $query->where('tenant_id', $tenantId);
            }
            $row = $query->first();

            if ($row === null) {
                return $default;
            }

            $decoded = json_decode((string) $row->value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->value;
        });
    }

    public static function setValue(string $key, mixed $value, ?int $tenantId = null): void
    {
        $tenantId ??= app(TenantContext::class)->id();
        $attributes = ['key' => $key];
        if ($tenantId !== null && Schema::hasColumn('shop_settings', 'tenant_id')) {
            $attributes['tenant_id'] = $tenantId;
        }

        static::query()->updateOrCreate(
            $attributes,
            ['value' => is_string($value) ? $value : json_encode($value)],
        );

        Cache::forget(self::cacheKey($key, $tenantId));
    }

    public static function flag(string $key, bool $default = false): bool
    {
        return (bool) static::getValue($key, $default);
    }

    private static function cacheKey(string $key, ?int $tenantId): string
    {
        return 'shop_setting:'.($tenantId ?: 'global').':'.$key;
    }
}
