<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_settings', function (Blueprint $table) {
            $table->id();
            $table->string('default_product_status', 30)->default('draft');
            $table->string('default_publication_status', 30)->default('not_published');
            $table->boolean('require_brand_on_create')->default(false);
            $table->boolean('require_primary_category_on_create')->default(false);
            $table->boolean('require_unit_on_create')->default(false);
            $table->boolean('auto_submit_for_review_on_create')->default(false);
            $table->string('sku_prefix', 20)->nullable();
            $table->foreignId('default_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->unsignedTinyInteger('max_media_per_product')->default(10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_settings');
    }
};
