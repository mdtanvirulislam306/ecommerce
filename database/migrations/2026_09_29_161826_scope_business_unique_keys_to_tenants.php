<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business identifiers that must be unique per shop, not across the whole platform.
     *
     * @var list<array{0: string, 1: string}>
     */
    private array $keys = [
        ['brands', 'slug'],
        ['categories', 'slug'],
        ['units', 'code'],
        ['attributes', 'code'],
        ['product_families', 'slug'],
        ['collections', 'slug'],
        ['products', 'slug'],
        ['products', 'sku'],
        ['product_variants', 'sku'],
        ['price_lists', 'code'],
        ['customer_groups', 'code'],
        ['coupons', 'code'],
        ['referrals', 'code'],
        ['couriers', 'code'],
        ['shipments', 'tracking_number'],
        ['warehouses', 'code'],
        ['stock_transfers', 'number'],
        ['inventory_serial_numbers', 'serial_number'],
        ['customers', 'code'],
        ['customer_segments', 'code'],
        ['lead_sources', 'code'],
        ['sales_orders', 'number'],
        ['sales_quotations', 'number'],
        ['sales_invoices', 'number'],
        ['sales_payments', 'number'],
        ['sales_returns', 'number'],
        ['sales_credit_notes', 'number'],
        ['suppliers', 'code'],
        ['supplier_groups', 'code'],
        ['purchase_orders', 'number'],
        ['purchase_returns', 'number'],
        ['purchase_payments', 'number'],
        ['online_orders', 'number'],
        ['store_settings', 'key'],
        ['theme_settings', 'code'],
        ['cms_pages', 'slug'],
        ['ecommerce_coupons', 'code'],
        ['pos_registers', 'code'],
        ['pos_orders', 'number'],
        ['pos_returns', 'number'],
        ['ledger_accounts', 'code'],
        ['journal_entries', 'number'],
        ['bank_accounts', 'code'],
        ['cost_centers', 'code'],
        ['profit_centers', 'code'],
        ['fixed_asset_categories', 'code'],
        ['fixed_assets', 'code'],
        ['hrm_employees', 'code'],
        ['hrm_departments', 'code'],
        ['hrm_designations', 'code'],
        ['marketing_coupons', 'code'],
        ['marketing_referrals', 'code'],
        ['setting_values', 'key'],
        ['roles', 'name'],
        ['roles', 'slug'],
        ['numbering_series', 'code'],
        ['tax_rates', 'code'],
        ['currencies', 'code'],
        ['payment_methods', 'code'],
        ['shipping_methods', 'code'],
        ['support_tickets', 'number'],
        ['support_categories', 'slug'],
    ];

    public function up(): void
    {
        foreach ($this->keys as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $column) {
                if (Schema::hasIndex($table, "{$table}_{$column}_unique")) {
                    $blueprint->dropUnique([$column]);
                }

                if (! Schema::hasIndex($table, "{$table}_tenant_id_{$column}_unique")) {
                    $blueprint->unique(['tenant_id', $column]);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->keys) as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $column) {
                if (Schema::hasIndex($table, "{$table}_tenant_id_{$column}_unique")) {
                    $blueprint->dropUnique(['tenant_id', $column]);
                }

                if (! Schema::hasIndex($table, "{$table}_{$column}_unique")) {
                    $blueprint->unique($column);
                }
            });
        }
    }
};
