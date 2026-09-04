<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('sku', 80)->nullable();
            $table->string('name');
            $table->decimal('quantity_ordered', 16, 4);
            $table->decimal('quantity_received', 16, 4)->default(0);
            $table->decimal('unit_cost', 16, 4);
            $table->decimal('line_total', 16, 4);
            $table->string('currency', 3)->default('BDT');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
