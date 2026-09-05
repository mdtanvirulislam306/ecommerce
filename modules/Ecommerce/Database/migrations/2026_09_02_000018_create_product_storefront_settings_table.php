<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_storefront_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_on_homepage')->default(false);
            $table->unsignedInteger('storefront_sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_featured', 'storefront_sort_order'], 'psettings_featured_sort_idx');
            $table->index(['show_on_homepage', 'storefront_sort_order'], 'psettings_homepage_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_storefront_settings');
    }
};
