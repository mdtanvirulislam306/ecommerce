<?php

namespace App\Core\Services;

use App\Core\Support\Service;
use App\Core\Tenant\TenantContext;
use Modules\Ecommerce\Models\StoreSetting;
use Modules\Settings\Models\PaymentMethod;
use Modules\Settings\Models\SettingValue;

/**
 * First-run setup progress for shop owner admin pages.
 *
 * Flags read existing tenant rows only. Provisioned tenant name, billing
 * complexity flags, and unsaved form defaults do not count.
 *
 * shop_name is Settings general `shop_name`.
 * business_profile is any field saved by the business profile form.
 * store_settings is Ecommerce store settings `store_name`.
 * payment_method is at least one Settings payment method for this tenant.
 */
final class SetupChecklistService extends Service
{
    /**
     * Fields persisted by BusinessProfileController.
     *
     * @var list<string>
     */
    private const BUSINESS_PROFILE_KEYS = [
        'company_name',
        'legal_name',
        'email',
        'phone',
        'address',
        'tax_id',
    ];

    /**
     * Inertia `setupChecklist` for the tenant bound to the current request.
     *
     * `progress` is an integer percent of the four flags (0, 25, 50, 75, 100).
     * `completed` is true only when every flag is true.
     *
     * @return array{
     *     shop_name: bool,
     *     business_profile: bool,
     *     store_settings: bool,
     *     payment_method: bool,
     *     completed: bool,
     *     progress: int
     * }
     */
    public function forCurrentTenant(): array
    {
        if (app(TenantContext::class)->id() === null) {
            return $this->checklist(false, false, false, false);
        }

        $settings = SettingValue::query()
            ->where(function ($query): void {
                $query->where(function ($general): void {
                    $general->where('group', 'general')->where('key', 'shop_name');
                })->orWhere(function ($business): void {
                    $business->where('group', 'business')->whereIn('key', self::BUSINESS_PROFILE_KEYS);
                });
            })
            ->get(['group', 'key', 'value']);

        $shopName = $this->isFilled(
            $settings->first(fn (SettingValue $row): bool => $row->group === 'general' && $row->key === 'shop_name')?->value
        );

        $businessProfile = $settings->contains(
            fn (SettingValue $row): bool => $row->group === 'business' && $this->isFilled($row->value)
        );

        $storeName = StoreSetting::query()->where('key', 'store_name')->value('value');

        return $this->checklist(
            $shopName,
            $businessProfile,
            $this->isFilled($storeName),
            PaymentMethod::query()->exists(),
        );
    }

    /**
     * @return array{
     *     shop_name: bool,
     *     business_profile: bool,
     *     store_settings: bool,
     *     payment_method: bool,
     *     completed: bool,
     *     progress: int
     * }
     */
    private function checklist(
        bool $shopName,
        bool $businessProfile,
        bool $storeSettings,
        bool $paymentMethod,
    ): array {
        $completedCount = (int) $shopName + (int) $businessProfile + (int) $storeSettings + (int) $paymentMethod;

        return [
            'shop_name' => $shopName,
            'business_profile' => $businessProfile,
            'store_settings' => $storeSettings,
            'payment_method' => $paymentMethod,
            'completed' => $completedCount === 4,
            'progress' => intdiv($completedCount * 100, 4),
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
