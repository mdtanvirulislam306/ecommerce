<?php

namespace Database\Seeders;

use App\Core\Contracts\PriceResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Enums\PaymentMethod;
use Modules\Ecommerce\Models\OnlineOrder;

class EcommerceOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('online_orders')->exists()) {
            return;
        }

        $product = DB::table('products')
            ->where('publication_status', 'published')
            ->where('status', '!=', 'archived')
            ->orderBy('id')
            ->first();

        if ($product === null) {
            $product = DB::table('products')->orderBy('id')->first();

            if ($product !== null) {
                DB::table('products')->where('id', $product->id)->update([
                    'status' => 'active',
                    'publication_status' => 'published',
                ]);
                $product = DB::table('products')->where('id', $product->id)->first();
            }
        } else {
            DB::table('products')->where('id', $product->id)->update([
                'status' => 'active',
                'publication_status' => 'published',
            ]);
        }

        if ($product === null) {
            return;
        }

        $resolved = app(PriceResolver::class)->resolve($product->id, 1);

        if (! ($resolved['resolved'] ?? false) || $resolved['price'] === null) {
            return;
        }

        $warehouseId = DB::table('warehouses')->where('is_default', true)->value('id')
            ?? DB::table('warehouses')->orderBy('id')->value('id');

        $price = (float) $resolved['price'];
        $currency = $resolved['currency'] ?? 'BDT';

        $order = OnlineOrder::query()->create([
            'number' => 'WEB-'.now()->format('Ymd').'-0001',
            'status' => OnlineOrderStatus::Pending,
            'customer_name' => 'Online Guest',
            'customer_email' => 'guest@example.com',
            'customer_phone' => '01710000000',
            'shipping_address' => "House 1, Road 2\nDhaka",
            'payment_method' => PaymentMethod::Cod,
            'currency' => $currency,
            'subtotal' => number_format($price, 4, '.', ''),
            'grand_total' => number_format($price, 4, '.', ''),
            'notes' => 'Sample web order from seeder',
            'warehouse_id' => $warehouseId,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'quantity' => '1.0000',
            'unit_price' => number_format($price, 4, '.', ''),
            'line_total' => number_format($price, 4, '.', ''),
            'currency' => $currency,
            'sort_order' => 0,
        ]);
    }
}
