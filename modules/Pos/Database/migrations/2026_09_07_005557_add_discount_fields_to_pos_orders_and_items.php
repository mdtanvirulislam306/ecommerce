<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->decimal('discount_total', 16, 4)->default(0)->after('subtotal');
        });

        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->decimal('discount_percent', 8, 4)->default(0)->after('unit_price');
            $table->decimal('discount_amount', 16, 4)->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropColumn('discount_total');
        });

        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discount_amount']);
        });
    }
};
