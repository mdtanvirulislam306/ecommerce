<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->string('field', 40);
            $table->string('from_value', 40)->nullable();
            $table->string('to_value', 40);
            $table->string('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sales_order_id', 'created_at']);
            $table->index('field');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_status_logs');
    }
};
