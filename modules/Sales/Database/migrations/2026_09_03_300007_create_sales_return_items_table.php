<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
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

            $table->index('sales_return_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
    }
};
