<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Purchase\Services\SupplierService;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::table('suppliers')->exists()) {
            app(SupplierService::class)->create([
                'name' => 'Demo Supplier Ltd',
                'code' => 'SUP-DEMO',
                'email' => 'supplier@example.com',
                'phone' => '01800000000',
                'address' => 'Dhaka',
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }

        if (DB::table('purchase_orders')->exists()) {
            return;
        }

        $supplier = DB::table('suppliers')->where('code', 'SUP-DEMO')->first()
            ?? DB::table('suppliers')->orderBy('id')->first();

        $product = DB::table('products')
            ->where('status', '!=', 'archived')
            ->where('type', 'simple')
            ->where('sku', 'BB-RICE-25')
            ->first()
            ?? DB::table('products')
                ->where('status', '!=', 'archived')
                ->where('type', 'simple')
                ->orderBy('id')
                ->first();

        $warehouse = DB::table('warehouses')->where('is_default', true)->first()
            ?? DB::table('warehouses')->orderBy('id')->first();

        if ($supplier === null || $product === null || $warehouse === null) {
            return;
        }

        try {
            app(PurchaseOrderService::class)->create([
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'notes' => 'Sample pending PO from seeder',
                'status' => 'pending',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 10,
                        'unit_cost' => 100,
                    ],
                ],
            ]);
        } catch (\Throwable) {
            // Skip if catalog/inventory not ready.
        }
    }
}
