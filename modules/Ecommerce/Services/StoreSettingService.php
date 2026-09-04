<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\StoreSetting;

class StoreSettingService extends Service
{
    public function get(string $key, mixed $default = null): mixed
    {
        $row = StoreSetting::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function getMany(array $keys, array $defaults = []): array
    {
        $rows = StoreSetting::query()
            ->whereIn('key', $keys)
            ->pluck('value', 'key');

        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $rows->get($key, $defaults[$key] ?? null);
        }

        return $result;
    }

    public function put(string $key, mixed $value): void
    {
        StoreSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function putMany(array $data): void
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                $this->put($key, $value);
            }
        });
    }
}
