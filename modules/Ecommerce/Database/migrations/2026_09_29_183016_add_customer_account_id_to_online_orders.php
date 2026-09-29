<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->foreignId('customer_account_id')->nullable()->after('customer_id')->constrained('customer_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_account_id');
        });
    }
};
