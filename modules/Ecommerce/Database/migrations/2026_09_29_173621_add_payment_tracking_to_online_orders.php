<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('unpaid')->after('payment_method');
            $table->string('payment_transaction_id', 40)->nullable()->index()->after('payment_status');
            $table->string('payment_validation_id', 80)->nullable()->after('payment_transaction_id');
            $table->string('payment_bank_transaction_id', 80)->nullable()->after('payment_validation_id');
            $table->string('payment_card_type', 60)->nullable()->after('payment_bank_transaction_id');
            $table->timestamp('paid_at')->nullable()->after('payment_card_type');
        });
    }

    public function down(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->dropIndex(['payment_transaction_id']);
            $table->dropColumn([
                'payment_status',
                'payment_transaction_id',
                'payment_validation_id',
                'payment_bank_transaction_id',
                'payment_card_type',
                'paid_at',
            ]);
        });
    }
};
