<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CatalogSeeder::class,
            CommerceSeeder::class,
            EcommerceSeeder::class,
            InventorySeeder::class,
            ShopDefaultsSeeder::class,
            SalesSeeder::class,
            PurchaseSeeder::class,
            CrmSeeder::class,
            AccountingSeeder::class,
            EcommerceOrderSeeder::class,
            PosSeeder::class,
            BillingSeeder::class,
        ]);
    }
}
