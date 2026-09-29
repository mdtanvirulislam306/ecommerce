<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_owner')->default(false)->after('is_platform_admin');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('description');
        });

        // Before permissions existed every shop user had full access; keep that for users without a role
        // and for each shop's first user, so nobody is locked out by this migration.
        DB::table('users')
            ->where('is_platform_admin', false)
            ->whereNotNull('tenant_id')
            ->whereNotIn('id', DB::table('role_user')->select('user_id'))
            ->update(['is_owner' => true]);

        $firstUserIds = DB::table('users')
            ->whereNotNull('tenant_id')
            ->groupBy('tenant_id')
            ->selectRaw('min(id) as id')
            ->pluck('id');

        DB::table('users')->whereIn('id', $firstUserIds)->update(['is_owner' => true]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_owner');
        });
    }
};
