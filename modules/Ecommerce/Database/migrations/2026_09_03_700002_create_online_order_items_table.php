<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_order_id')->constrained('online_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('sku', 80)->nullable();
            $table->string('name');
            $table->decimal('quantity', 16, 4);
            $table->decimal('unit_price', 16, 4);
            $table->decimal('line_total', 16, 4);
            $table->string('currency', 3)->default('BDT');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('online_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_order_items');
    }
};
