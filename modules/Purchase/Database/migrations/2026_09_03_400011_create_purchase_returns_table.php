<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->string('status', 30)->default('completed');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->unsignedBigInteger('warehouse_id')->nullable()->index();
            $table->string('currency', 3)->default('BDT');
            $table->decimal('subtotal', 16, 4)->default(0);
            $table->decimal('grand_total', 16, 4)->default(0);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('returned_at');
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->string('sku', 80)->nullable();
            $table->string('name');
            $table->decimal('quantity', 16, 4);
            $table->decimal('unit_cost', 16, 4)->default(0);
            $table->decimal('line_total', 16, 4)->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
