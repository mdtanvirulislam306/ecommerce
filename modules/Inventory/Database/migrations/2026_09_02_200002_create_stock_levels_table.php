<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('on_hand', 16, 4)->default(0);
            $table->decimal('reserved', 16, 4)->default(0);
            $table->decimal('reorder_point', 16, 4)->default(0);
            $table->timestamps();

            $table->unique(
                ['warehouse_id', 'product_id', 'product_variant_id'],
                'stock_levels_unique_sku',
            );
            $table->index(['product_id', 'product_variant_id'], 'stock_levels_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
