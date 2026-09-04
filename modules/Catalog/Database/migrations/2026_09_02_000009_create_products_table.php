<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_family_id')->nullable()->constrained('product_families')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('primary_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('type', 20); // simple | variant
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('internal_code', 60)->nullable();
            $table->string('sku', 80)->nullable()->unique();
            $table->string('barcode', 80)->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('publication_status', 30)->default('not_published');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('publication_status');
            $table->index('brand_id');
            $table->index('primary_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
