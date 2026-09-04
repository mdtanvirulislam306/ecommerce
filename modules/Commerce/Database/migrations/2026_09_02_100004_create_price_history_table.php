<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $table->foreignId('price_list_item_id')->nullable()->constrained('price_list_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('action', 20);
            $table->decimal('old_price', 16, 4)->nullable();
            $table->decimal('new_price', 16, 4)->nullable();
            $table->unsignedInteger('min_quantity')->default(1);
            $table->string('currency', 3)->default('BDT');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['price_list_id', 'created_at'], 'ph_list_created_idx');
            $table->index(['product_id', 'created_at'], 'ph_product_created_idx');
            $table->index('action', 'ph_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};
