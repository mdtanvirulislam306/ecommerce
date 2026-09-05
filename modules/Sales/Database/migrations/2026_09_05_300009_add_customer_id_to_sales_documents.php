<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('status')->constrained('customers')->nullOnDelete();
        });

        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('status')->constrained('customers')->nullOnDelete();
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('sales_order_id')->constrained('customers')->nullOnDelete();
        });

        Schema::table('sales_returns', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('sales_order_id')->constrained('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
