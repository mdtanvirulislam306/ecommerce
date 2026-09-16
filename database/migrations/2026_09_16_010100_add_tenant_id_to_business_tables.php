<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'brands', 'categories', 'units', 'unit_conversions', 'attributes', 'attribute_options',
        'product_families', 'collections', 'products', 'product_variants', 'product_media',
        'catalog_settings',
        'price_lists', 'price_list_items', 'customer_groups', 'promotions', 'coupons',
        'loyalty_programs', 'loyalty_points', 'loyalty_transactions', 'referrals', 'commissions',
        'couriers', 'shipments', 'customer_wallets', 'wallet_transactions',
        'warehouses', 'stock_levels', 'stock_movements', 'stock_transfers', 'stock_transfer_items',
        'inventory_batches', 'inventory_serial_numbers',
        'customers', 'leads', 'crm_activities', 'customer_segments', 'lead_sources',
        'sales_orders', 'sales_order_items', 'sales_quotations', 'sales_quotation_items',
        'sales_invoices', 'sales_invoice_items', 'sales_payments', 'sales_returns',
        'sales_return_items', 'sales_credit_notes', 'sales_credit_note_items', 'sales_order_status_logs',
        'suppliers', 'supplier_groups', 'purchase_orders', 'purchase_order_items',
        'purchase_returns', 'purchase_return_items', 'purchase_payments',
        'online_orders', 'store_settings', 'store_domains', 'theme_settings',
        'cms_pages', 'cms_menus', 'ecommerce_coupons', 'ecommerce_promotions', 'product_reviews',
        'product_storefront_settings',
        'pos_registers', 'pos_sessions', 'pos_orders', 'pos_order_items',
        'pos_cash_movements', 'pos_returns', 'pos_return_items',
        'ledger_accounts', 'journal_entries', 'journal_lines', 'bank_accounts', 'bank_transactions',
        'cost_centers', 'profit_centers', 'budgets', 'fixed_asset_categories', 'fixed_assets',
        'fixed_asset_depreciations', 'fiscal_years', 'financial_closings',
        'hrm_employees', 'hrm_departments', 'hrm_designations', 'hrm_attendances', 'hrm_leaves',
        'hrm_payrolls', 'hrm_salary_structures',
        'stories', 'marketing_campaigns', 'marketing_segments', 'marketing_promotions',
        'marketing_coupons', 'marketing_loyalty_settings', 'marketing_referrals',
        'setting_values', 'roles', 'numbering_series', 'tax_rates', 'currencies',
        'payment_methods', 'shipping_methods', 'audit_logs',
        'support_tickets', 'support_categories', 'support_canned_responses',
        'tasks', 'workflows', 'approval_requests', 'approval_policies',
        'automation_rules', 'scheduled_tasks', 'workflow_logs',
        'notification_templates', 'notification_channel_preferences', 'user_notifications',
        'media_library_items', 'documents',
    ];

    public function up(): void
    {
        $tenantId = DB::table('tenants')->where('slug', 'default')->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        if (! $tenantId) {
            return;
        }

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $blueprint->index('tenant_id', substr('idx_'.$table.'_tenant', 0, 64));
            });

            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                try {
                    $blueprint->dropForeign(['tenant_id']);
                } catch (Throwable) {
                }
                try {
                    $blueprint->dropIndex(substr('idx_'.$table.'_tenant', 0, 64));
                } catch (Throwable) {
                }
                $blueprint->dropColumn('tenant_id');
            });
        }
    }
};
