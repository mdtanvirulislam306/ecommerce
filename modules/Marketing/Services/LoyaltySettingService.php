<?php

namespace Modules\Marketing\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Marketing\Models\LoyaltySetting;

class LoyaltySettingService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return LoyaltySetting::query()
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (LoyaltySetting $setting) => $this->format($setting));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LoyaltySetting
    {
        return LoyaltySetting::query()->create([
            'name' => $data['name'],
            'points_per_currency' => $data['points_per_currency'] ?? 1,
            'redemption_rate' => $data['redemption_rate'] ?? 0.01,
            'is_active' => $data['is_active'] ?? true,
            'settings' => $data['settings'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LoyaltySetting $setting, array $data): LoyaltySetting
    {
        $setting->update([
            'name' => $data['name'] ?? $setting->name,
            'points_per_currency' => $data['points_per_currency'] ?? $setting->points_per_currency,
            'redemption_rate' => $data['redemption_rate'] ?? $setting->redemption_rate,
            'is_active' => $data['is_active'] ?? $setting->is_active,
            'settings' => $data['settings'] ?? $setting->settings,
        ]);

        return $setting->fresh();
    }

    public function delete(LoyaltySetting $setting): void
    {
        $setting->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(LoyaltySetting $setting): array
    {
        return [
            'id' => $setting->id,
            'name' => $setting->name,
            'points_per_currency' => $setting->points_per_currency,
            'redemption_rate' => $setting->redemption_rate,
            'is_active' => $setting->is_active,
            'settings' => $setting->settings,
            'created_at' => $setting->created_at?->toIso8601String(),
        ];
    }
}
