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
            SettingsSeeder::class,
            CatalogSeeder::class,
            CommerceSeeder::class,
            InventorySeeder::class,
            ShopDefaultsSeeder::class,
            CrmSeeder::class,
            SalesSeeder::class,
            PurchaseSeeder::class,
            EcommerceSeeder::class,
            DemoStorefrontImagesSeeder::class,
            EcommerceOrderSeeder::class,
            AccountingSeeder::class,
            PosSeeder::class,
            HrmSeeder::class,
            SupportSeeder::class,
            MarketingSeeder::class,
            StoriesSeeder::class,
            TasksSeeder::class,
            NotificationsSeeder::class,
            WorkflowSeeder::class,
            FilesSeeder::class,
            BillingSeeder::class,
        ]);
    }
}
