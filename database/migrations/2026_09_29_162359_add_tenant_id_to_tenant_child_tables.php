<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose models are tenant-scoped but were missed by the original tenant_id wave,
     * mapped to the parent table and foreign key their tenant is inherited from.
     *
     * @var array<string, array{parent: string, foreign_key: string}>
     */
    private array $tables = [
        'price_history' => ['parent' => 'price_lists', 'foreign_key' => 'price_list_id'],
        'product_attribute_values' => ['parent' => 'products', 'foreign_key' => 'product_id'],
        'product_variant_attribute_values' => ['parent' => 'product_variants', 'foreign_key' => 'product_variant_id'],
        'online_order_items' => ['parent' => 'online_orders', 'foreign_key' => 'online_order_id'],
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => ['parent' => $parent, 'foreign_key' => $foreignKey]) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $blueprint->index('tenant_id', 'idx_'.$table.'_tenant');
            });

            DB::table($table)
                ->whereNull('tenant_id')
                ->update([
                    'tenant_id' => DB::raw("(select {$parent}.tenant_id from {$parent} where {$parent}.id = {$table}.{$foreignKey})"),
                ]);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeign(['tenant_id']);
                $blueprint->dropIndex('idx_'.$table.'_tenant');
                $blueprint->dropColumn('tenant_id');
            });
        }
    }
};
