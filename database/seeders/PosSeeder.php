<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Pos\Services\PosRegisterService;
use Modules\Pos\Services\PosSaleService;

class PosSeeder extends Seeder
{
    public function run(): void
    {
        app(PosRegisterService::class)->ensureDefault();

        if (DB::table('pos_orders')->exists()) {
            return;
        }

        $product = DB::table('products')
            ->where('status', '!=', 'archived')
            ->where('type', 'simple')
            ->where('sku', 'BB-DAL-1')
            ->first()
            ?? DB::table('products')->where('status', '!=', 'archived')->where('type', 'simple')->orderBy('id')->first();

        $customer = DB::table('customers')->where('code', 'CUS-00001')->first();
        $adminId = User::query()->where('email', 'admin@admin.com')->value('id');

        if ($product === null) {
            return;
        }

        try {
            app(PosSaleService::class)->completeSale([
                'customer_id' => $customer?->id,
                'customer_name' => $customer->name ?? 'Walk-in',
                'amount_tendered' => 2000,
                'notes' => 'Sample POS cash sale from seeder',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ], $adminId);
        } catch (\Throwable) {
            // Skip if price or stock is not ready.
        }
    }
}
