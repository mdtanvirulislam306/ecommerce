<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Services\SalesOrderService;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('sales_orders')->exists()) {
            return;
        }

        $product = DB::table('products')
            ->where('status', '!=', 'archived')
            ->where('type', 'simple')
            ->orderBy('id')
            ->first();

        $warehouse = DB::table('warehouses')->where('is_default', true)->first()
            ?? DB::table('warehouses')->orderBy('id')->first();

        $group = DB::table('customer_groups')->where('code', 'retail')->first();

        if ($product === null || $warehouse === null) {
            return;
        }

        try {
            app(SalesOrderService::class)->create([
                'customer_name' => 'Walk-in Customer',
                'customer_email' => 'walkin@example.com',
                'customer_phone' => '01700000000',
                'customer_group_id' => $group?->id,
                'warehouse_id' => $warehouse->id,
                'notes' => 'Sample pending order from seeder',
                'status' => 'pending',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ]);
        } catch (\Throwable) {
            // Skip if price/stock not available yet.
        }
    }
}
