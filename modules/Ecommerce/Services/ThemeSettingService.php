<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Models\ThemeSetting;

class ThemeSettingService extends Service
{
    /**
     * @return array<int, array{code: string, name: string, description: string, default_settings: array<string, mixed>}>
     */
    public function libraryPresets(): array
    {
        return [
            [
                'code' => 'classic',
                'name' => 'Classic Retail',
                'description' => 'Clean layout with sidebar navigation for general stores.',
                'default_settings' => [
                    'primary_color' => '#1e3a5f',
                    'logo_url' => '',
                    'header_html' => '',
                ],
            ],
            [
                'code' => 'modern',
                'name' => 'Modern Shop',
                'description' => 'Bold hero sections and card-based product grids.',
                'default_settings' => [
                    'primary_color' => '#ea580c',
                    'logo_url' => '',
                    'header_html' => '',
                ],
            ],
            [
                'code' => 'minimal',
                'name' => 'Minimal',
                'description' => 'Typography-focused layout with generous whitespace.',
                'default_settings' => [
                    'primary_color' => '#111827',
                    'logo_url' => '',
                    'header_html' => '',
                ],
            ],
        ];
    }

    /**
     * @return Collection<int, ThemeSetting>
     */
    public function installed(): Collection
    {
        return ThemeSetting::query()
            ->where('is_installed', true)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    public function active(): ?ThemeSetting
    {
        return ThemeSetting::query()
            ->where('is_active', true)
            ->where('is_installed', true)
            ->first();
    }

    public function install(string $code): ThemeSetting
    {
        $preset = collect($this->libraryPresets())->firstWhere('code', $code);

        if ($preset === null) {
            throw ValidationException::withMessages([
                'code' => 'Unknown theme preset.',
            ]);
        }

        $existing = ThemeSetting::query()->where('code', $code)->first();

        if ($existing !== null) {
            if ($existing->is_installed) {
                throw ValidationException::withMessages([
                    'code' => 'Theme is already installed.',
                ]);
            }

            $existing->update([
                'is_installed' => true,
                'settings' => $preset['default_settings'],
            ]);

            return $existing->fresh();
        }

        return ThemeSetting::query()->create([
            'code' => $preset['code'],
            'name' => $preset['name'],
            'settings' => $preset['default_settings'],
            'is_installed' => true,
            'is_active' => ThemeSetting::query()->where('is_installed', true)->doesntExist(),
            'is_published' => false,
        ]);
    }

    public function activate(ThemeSetting $theme): ThemeSetting
    {
        return DB::transaction(function () use ($theme) {
            ThemeSetting::query()->update(['is_active' => false]);
            $theme->update(['is_active' => true, 'is_installed' => true]);

            return $theme->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function updateSettings(ThemeSetting $theme, array $settings): ThemeSetting
    {
        return DB::transaction(function () use ($theme, $settings) {
            $theme->update(['settings' => $settings]);

            return $theme->fresh();
        });
    }

    public function publishActive(): ThemeSetting
    {
        $active = $this->active();

        if ($active === null) {
            throw ValidationException::withMessages([
                'theme' => 'No active theme to publish. Install and activate a theme first.',
            ]);
        }

        return DB::transaction(function () use ($active) {
            ThemeSetting::query()->update(['is_published' => false]);
            $active->update(['is_published' => true]);

            return $active->fresh();
        });
    }
}
