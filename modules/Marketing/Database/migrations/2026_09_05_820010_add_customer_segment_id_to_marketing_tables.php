<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_segments', function (Blueprint $table) {
            $table->foreignId('customer_segment_id')
                ->nullable()
                ->after('description')
                ->constrained('customer_segments')
                ->nullOnDelete();
        });

        Schema::table('marketing_campaigns', function (Blueprint $table) {
            $table->foreignId('customer_segment_id')
                ->nullable()
                ->after('audience_count')
                ->constrained('customer_segments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_segment_id');
        });

        Schema::table('marketing_segments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_segment_id');
        });
    }
};
