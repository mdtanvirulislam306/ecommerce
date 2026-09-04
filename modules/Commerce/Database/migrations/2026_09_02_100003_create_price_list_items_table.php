<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('price', 16, 4);
            $table->unsignedInteger('min_quantity')->default(1);
            $table->timestamps();

            $table->index(['price_list_id', 'product_id']);
            $table->index(['price_list_id', 'product_variant_id']);
            $table->unique(
                ['price_list_id', 'product_id', 'product_variant_id', 'min_quantity'],
                'price_list_items_unique_target_qty',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
