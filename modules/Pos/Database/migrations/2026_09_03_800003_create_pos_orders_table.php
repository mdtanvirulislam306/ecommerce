<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->string('status', 30)->default('completed');
            $table->foreignId('pos_register_id')->nullable()->constrained('pos_registers')->nullOnDelete();
            $table->foreignId('pos_session_id')->nullable()->constrained('pos_sessions')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('payment_method', 30)->default('cash');
            $table->string('currency', 3)->default('BDT');
            $table->decimal('subtotal', 16, 4)->default(0);
            $table->decimal('grand_total', 16, 4)->default(0);
            $table->decimal('amount_tendered', 16, 4)->nullable();
            $table->decimal('change_due', 16, 4)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_orders');
    }
};
