<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->string('coupon_code', 40)->nullable()->after('subtotal');
            $table->decimal('discount_total', 16, 4)->default(0)->after('coupon_code');
            $table->string('delivery_zone', 80)->nullable()->after('discount_total');
            $table->decimal('shipping_fee', 16, 4)->default(0)->after('delivery_zone');
        });
    }

    public function down(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->dropColumn(['coupon_code', 'discount_total', 'delivery_zone', 'shipping_fee']);
        });
    }
};
