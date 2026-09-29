<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Customers open their order through an unguessable token instead of the sequential id.
     */
    public function up(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->string('access_token', 64)->nullable()->unique()->after('number');
        });

        DB::table('online_orders')->whereNull('access_token')->orderBy('id')->each(function (object $order) {
            DB::table('online_orders')->where('id', $order->id)->update(['access_token' => Str::random(40)]);
        });
    }

    public function down(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->dropUnique(['access_token']);
            $table->dropColumn('access_token');
        });
    }
};
