<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\Currency;
use Modules\Settings\Models\NumberingSeries;
use Modules\Settings\Models\PaymentMethod;
use Modules\Settings\Models\ShippingMethod;
use Modules\Settings\Models\TaxRate;
use Modules\Settings\Services\SettingService;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SettingService::class);

        $settings->saveGroup('general', [
            'shop_name' => 'Budget & Bazar',
            'timezone' => 'Asia/Dhaka',
            'locale' => 'en',
            'default_currency' => 'BDT',
        ]);

        $settings->saveGroup('business', [
            'company_name' => 'Budget & Bazar',
            'legal_name' => 'Budget & Bazar Limited',
            'email' => 'hello@budgetandbazar.example',
            'phone' => '01700000000',
            'address' => 'Kawran Bazar, Dhaka 1215',
            'tax_id' => 'BIN-000123456',
        ]);

        Currency::query()->firstOrCreate(
            ['code' => 'BDT'],
            [
                'name' => 'Bangladeshi Taka',
                'symbol' => '৳',
                'exchange_rate' => 1,
                'is_default' => true,
                'is_active' => true,
            ],
        );

        TaxRate::query()->firstOrCreate(
            ['code' => 'VAT15'],
            [
                'name' => 'VAT 15%',
                'rate' => 15,
                'is_active' => true,
            ],
        );

        PaymentMethod::query()->firstOrCreate(
            ['code' => 'COD'],
            [
                'name' => 'Cash on Delivery',
                'is_active' => true,
                'config' => ['channel' => 'offline'],
            ],
        );

        PaymentMethod::query()->firstOrCreate(
            ['code' => 'BKASH'],
            [
                'name' => 'bKash',
                'is_active' => true,
                'config' => ['wallet' => '01700000000'],
            ],
        );

        ShippingMethod::query()->firstOrCreate(
            ['code' => 'PATHAO'],
            [
                'name' => 'Pathao Courier',
                'flat_rate' => 80,
                'is_active' => true,
            ],
        );

        ShippingMethod::query()->firstOrCreate(
            ['code' => 'STORE_PICKUP'],
            [
                'name' => 'Store pickup',
                'flat_rate' => 0,
                'is_active' => true,
            ],
        );

        foreach ([
            ['code' => 'SO', 'name' => 'Sales orders', 'prefix' => 'SO-'],
            ['code' => 'QT', 'name' => 'Quotations', 'prefix' => 'QT-'],
            ['code' => 'CUS', 'name' => 'Customers', 'prefix' => 'CUS-'],
            ['code' => 'PO', 'name' => 'Purchase orders', 'prefix' => 'PO-'],
            ['code' => 'WEB', 'name' => 'Online orders', 'prefix' => 'WEB-'],
            ['code' => 'POS', 'name' => 'POS receipts', 'prefix' => 'POS-'],
        ] as $series) {
            NumberingSeries::query()->firstOrCreate(
                ['code' => $series['code']],
                [
                    'name' => $series['name'],
                    'prefix' => $series['prefix'],
                    'next_number' => 1,
                    'pad_length' => 5,
                    'is_active' => true,
                ],
            );
        }
    }
}
