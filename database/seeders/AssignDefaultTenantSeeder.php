<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Demo seeders run without a bound shop (and without model events), so their rows land with a null tenant_id.
 * This hands every such row to the Default Shop so the seeded data is visible after login.
 */
class AssignDefaultTenantSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const SKIPPED_TABLES = ['users', 'tenants', 'tenant_domains', 'tenant_module_overrides'];

    public function run(): void
    {
        $tenantId = Tenant::query()->where('slug', 'default')->value('id');

        if ($tenantId === null) {
            return;
        }

        $tables = collect(Schema::getTables())
            ->pluck('name')
            ->unique()
            ->reject(fn (string $table) => in_array($table, self::SKIPPED_TABLES, true))
            ->filter(fn (string $table) => Schema::hasColumn($table, 'tenant_id'));

        foreach ($tables as $table) {
            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        }
    }
}
