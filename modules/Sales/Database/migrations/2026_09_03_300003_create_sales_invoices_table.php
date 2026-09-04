<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->string('status', 30)->default('due');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 40)->nullable();
            $table->string('currency', 3)->default('BDT');
            $table->decimal('subtotal', 16, 4)->default(0);
            $table->decimal('tax_total', 16, 4)->default(0);
            $table->decimal('grand_total', 16, 4)->default(0);
            $table->decimal('amount_paid', 16, 4)->default(0);
            $table->decimal('amount_due', 16, 4)->default(0);
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
