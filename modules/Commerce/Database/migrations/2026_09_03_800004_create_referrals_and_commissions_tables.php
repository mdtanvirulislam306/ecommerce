<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('referrer_name');
            $table->string('referrer_email')->nullable();
            $table->string('referee_email')->nullable();
            $table->string('status', 20)->default('pending');
            $table->decimal('reward_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20)->default('percentage');
            $table->decimal('rate', 12, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('referrals');
    }
};
