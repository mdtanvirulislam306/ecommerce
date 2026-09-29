<?php

namespace App\Core\Services;

use App\Core\Support\Service;
use App\Core\Tenant\TenantContext;
use Modules\Ecommerce\Models\StoreSetting;
use Modules\Settings\Models\PaymentMethod;
use Modules\Settings\Models\SettingValue;

/**
 * First-run setup progress for the owner dashboard.
 *
 * shop_name is the Settings General shop_name field, non-empty after trim.
 * business_profile is a saved business payload whose company_name is non-empty.
 * That update validates every field as optional, so company_name is the primary name.
 * store_settings is Ecommerce store settings store_name, which that form requires.
 * payment_method is at least one active Settings payment method.
 * setup_dismissed_at does not change completed.
 */
final class SetupChecklistService extends Service
{
    /**
     * Inertia `setupChecklist` for the tenant bound to the current request.
     *
     * `progress` is how many of the four flags are true (0–4).
     * `completed` is true only when every flag is true.
     *
     * @return array{
     *     shop_name: bool,
     *     business_profile: bool,
     *     store_settings: bool,
     *     payment_method: bool,
     *     completed: bool,
     *     progress: int,
     *     setup_dismissed_at: string|null
     * }
     */
    public function forCurrentTenant(): array
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant === null) {
            return $this->checklist(false, false, false, false, null);
        }

        $settings = SettingValue::query()
            ->where(function ($query): void {
                $query->where(function ($general): void {
                    $general->where('group', 'general')->where('key', 'shop_name');
                })->orWhere(function ($business): void {
                    $business->where('group', 'business')->where('key', 'company_name');
                });
            })
            ->get(['group', 'key', 'value']);

        $shopName = $this->isFilled(
            $settings->first(fn (SettingValue $row): bool => $row->group === 'general' && $row->key === 'shop_name')?->value
        );
        $businessProfile = $this->isFilled(
            $settings->first(fn (SettingValue $row): bool => $row->group === 'business' && $row->key === 'company_name')?->value
        );
        $storeName = StoreSetting::query()->where('key', 'store_name')->value('value');

        return $this->checklist(
            $shopName,
            $businessProfile,
            $this->isFilled($storeName),
            PaymentMethod::query()->where('is_active', true)->exists(),
            $tenant->setup_dismissed_at?->toIso8601String(),
        );
    }

    /**
     * Record that the current shop dismissed the first-run checklist.
     * Does not change the four derived flags or `completed`.
     */
    public function dismissForCurrentTenant(): void
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant === null) {
            abort(403);
        }

        $tenant->update([
            'setup_dismissed_at' => now(),
        ]);
    }

    /**
     * @return array{
     *     shop_name: bool,
     *     business_profile: bool,
     *     store_settings: bool,
     *     payment_method: bool,
     *     completed: bool,
     *     progress: int,
     *     setup_dismissed_at: string|null
     * }
     */
    private function checklist(
        bool $shopName,
        bool $businessProfile,
        bool $storeSettings,
        bool $paymentMethod,
        ?string $setupDismissedAt,
    ): array {
        $completedCount = (int) $shopName + (int) $businessProfile + (int) $storeSettings + (int) $paymentMethod;

        return [
            'shop_name' => $shopName,
            'business_profile' => $businessProfile,
            'store_settings' => $storeSettings,
            'payment_method' => $paymentMethod,
            'completed' => $completedCount === 4,
            'progress' => $completedCount,
            'setup_dismissed_at' => $setupDismissedAt,
        ];
    }

    /**
     * Text settings are stored raw, except nulls, which SettingValue::setValue JSON-encodes.
     * Decode with the same rule as SettingValue::groupValues() before checking for text.
     */
    private function isFilled(mixed $value): bool
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return trim((string) $value) !== '';
    }
}
