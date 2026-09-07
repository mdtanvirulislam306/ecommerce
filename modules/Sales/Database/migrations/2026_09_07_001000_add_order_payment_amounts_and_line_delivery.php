<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->decimal('amount_paid', 16, 4)->default(0)->after('grand_total');
            $table->decimal('amount_due', 16, 4)->default(0)->after('amount_paid');
        });

        DB::table('sales_orders')->update([
            'amount_due' => DB::raw('grand_total'),
        ]);

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->string('delivery_status', 30)->default('pending')->after('currency');
            $table->decimal('quantity_delivered', 16, 4)->default(0)->after('delivery_status');
            $table->index('delivery_status');
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropIndex(['delivery_status']);
            $table->dropColumn(['delivery_status', 'quantity_delivered']);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'amount_due']);
        });
    }
};
