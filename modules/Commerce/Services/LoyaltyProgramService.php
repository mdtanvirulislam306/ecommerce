<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Modules\Commerce\Models\LoyaltyProgram;

class LoyaltyProgramService extends Service
{
    public function get(): ?LoyaltyProgram
    {
        return LoyaltyProgram::query()->first();
    }

    public function create(array $data): LoyaltyProgram
    {
        return LoyaltyProgram::query()->create([
            'name' => $data['name'],
            'points_per_currency' => $data['points_per_currency'],
            'redemption_rate' => $data['redemption_rate'],
            'is_active' => $data['is_active'] ?? true,
            'settings' => $data['settings'] ?? null,
        ]);
    }

    public function update(LoyaltyProgram $program, array $data): LoyaltyProgram
    {
        $program->update([
            'name' => $data['name'] ?? $program->name,
            'points_per_currency' => $data['points_per_currency'] ?? $program->points_per_currency,
            'redemption_rate' => $data['redemption_rate'] ?? $program->redemption_rate,
            'is_active' => $data['is_active'] ?? $program->is_active,
            'settings' => array_key_exists('settings', $data) ? $data['settings'] : $program->settings,
        ]);

        return $program->fresh();
    }
}
