<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('customer_segment_customer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_segment_id')->constrained('customer_segments')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_segment_id', 'customer_id'], 'customer_segment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_segment_customer');
        Schema::dropIfExists('customer_segments');
    }
};
