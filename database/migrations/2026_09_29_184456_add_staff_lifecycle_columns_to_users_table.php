<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('invited_by')->nullable()->after('is_owner')->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable()->after('invited_by');
            $table->string('invitation_token', 64)->nullable()->unique()->after('invited_at');
            $table->timestamp('invitation_accepted_at')->nullable()->after('invitation_token');
            $table->timestamp('deactivated_at')->nullable()->after('invitation_accepted_at');
            $table->timestamp('last_login_at')->nullable()->after('deactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by');
            $table->dropUnique(['invitation_token']);
            $table->dropColumn(['invited_at', 'invitation_token', 'invitation_accepted_at', 'deactivated_at', 'last_login_at']);
        });
    }
};
