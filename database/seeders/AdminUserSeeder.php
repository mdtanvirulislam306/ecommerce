<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the platform owner (sees every shop) and the Default Shop's owner.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@admin.com', 'tenant_id' => null],
            [
                'name' => 'Platform Owner',
                'is_platform_admin' => true,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $defaultTenantId = Tenant::query()->where('slug', 'default')->value('id');

        if ($defaultTenantId === null) {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => 'owner@admin.com', 'tenant_id' => $defaultTenantId],
            [
                'name' => 'Shop Owner',
                'is_platform_admin' => false,
                'is_owner' => true,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
