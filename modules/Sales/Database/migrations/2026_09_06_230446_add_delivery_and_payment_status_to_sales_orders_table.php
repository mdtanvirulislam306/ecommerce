<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('delivery_status', 30)->default('pending')->after('status');
            $table->string('payment_status', 30)->default('unpaid')->after('delivery_status');

            $table->index('delivery_status');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex(['delivery_status']);
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['delivery_status', 'payment_status']);
        });
    }
};
