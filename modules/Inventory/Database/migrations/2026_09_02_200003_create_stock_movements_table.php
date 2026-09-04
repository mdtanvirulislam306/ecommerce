<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('type', 40);
            $table->decimal('quantity', 16, 4);
            $table->decimal('quantity_before', 16, 4)->default(0);
            $table->decimal('quantity_after', 16, 4)->default(0);
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'created_at'], 'stock_movements_wh_created_idx');
            $table->index(['product_id', 'product_variant_id'], 'stock_movements_product_idx');
            $table->index(['type', 'created_at'], 'stock_movements_type_idx');
            $table->index(['reference_type', 'reference_id'], 'stock_movements_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
