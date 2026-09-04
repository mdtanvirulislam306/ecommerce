<?php

namespace App\Core\Support;

use Modules\Billing\Models\ShopSetting;

class ShopComplexity
{
    public static function multiPrice(): bool
    {
        return ShopSetting::flag('multi_price', false);
    }

    public static function multiWarehouse(): bool
    {
        return ShopSetting::flag('multi_warehouse', false);
    }

    public static function multiBranch(): bool
    {
        return ShopSetting::flag('multi_branch', false);
    }

    /**
     * @return array{multi_price: bool, multi_warehouse: bool, multi_branch: bool}
     */
    public static function flags(): array
    {
        return [
            'multi_price' => self::multiPrice(),
            'multi_warehouse' => self::multiWarehouse(),
            'multi_branch' => self::multiBranch(),
        ];
    }
}
