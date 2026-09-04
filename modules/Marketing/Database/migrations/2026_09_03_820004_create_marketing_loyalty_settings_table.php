<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_loyalty_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('points_per_currency', 12, 4)->default(1);
            $table->decimal('redemption_rate', 12, 4)->default(0.01);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_loyalty_settings');
    }
};
