<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_referrals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('referrer_name');
            $table->string('referrer_email');
            $table->string('referee_email')->nullable();
            $table->string('status', 30)->default('pending');
            $table->decimal('reward_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['status', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_referrals');
    }
};
