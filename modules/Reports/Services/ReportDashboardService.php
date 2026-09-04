<?php

namespace Modules\Reports\Services;

use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportDashboardService extends Service
{
    public function overview(): array
    {
        return [
            'sales_orders' => $this->countIf('sales_orders'),
            'purchase_orders' => $this->countIf('purchase_orders'),
            'products' => $this->countIf('products'),
            'customers' => $this->countIf('customers'),
            'online_orders' => $this->countIf('online_orders'),
            'pos_sales' => $this->countIf('pos_sales'),
            'employees' => $this->countIf('hrm_employees'),
            'tickets' => $this->countIf('support_tickets'),
        ];
    }

    public function sales(): array
    {
        return [
            'orders' => $this->countIf('sales_orders'),
            'confirmed' => $this->countWhere('sales_orders', 'status', 'confirmed'),
            'cancelled' => $this->countWhere('sales_orders', 'status', 'cancelled'),
            'total_amount' => $this->sumIf('sales_orders', 'grand_total'),
        ];
    }

    public function purchase(): array
    {
        return [
            'orders' => $this->countIf('purchase_orders'),
            'received' => $this->countWhere('purchase_orders', 'status', 'received'),
            'total_amount' => $this->sumIf('purchase_orders', 'grand_total'),
        ];
    }

    public function inventory(): array
    {
        return [
            'warehouses' => $this->countIf('warehouses'),
            'stock_rows' => $this->countIf('stock_levels'),
            'movements' => $this->countIf('stock_movements'),
            'on_hand' => $this->sumIf('stock_levels', 'quantity'),
        ];
    }

    public function crm(): array
    {
        return [
            'leads' => $this->countIf('leads'),
            'customers' => $this->countIf('customers'),
            'activities' => $this->countIf('crm_activities'),
        ];
    }

    public function ecommerce(): array
    {
        return [
            'orders' => $this->countIf('online_orders'),
            'pending' => $this->countWhere('online_orders', 'status', 'pending'),
            'confirmed' => $this->countWhere('online_orders', 'status', 'confirmed'),
        ];
    }

    public function pos(): array
    {
        return [
            'sales' => $this->countIf('pos_sales'),
            'sessions' => $this->countIf('pos_sessions'),
            'total_amount' => $this->sumIf('pos_sales', 'grand_total'),
        ];
    }

    public function accounting(): array
    {
        return [
            'accounts' => $this->countIf('accounts'),
            'journals' => $this->countIf('journal_entries'),
        ];
    }

    public function hr(): array
    {
        return [
            'employees' => $this->countIf('hrm_employees'),
            'attendance' => $this->countIf('hrm_attendances'),
            'leave' => $this->countIf('hrm_leaves'),
            'payroll' => $this->countIf('hrm_payrolls'),
        ];
    }

    public function custom(): array
    {
        return ['note' => 'Custom report builder lands in a later phase.', 'saved_reports' => 0];
    }

    private function countIf(string $table): int
    {
        return Schema::hasTable($table) ? (int) DB::table($table)->count() : 0;
    }

    private function countWhere(string $table, string $column, mixed $value): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (int) DB::table($table)->where($column, $value)->count();
    }

    private function sumIf(string $table, string $column): float
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (float) DB::table($table)->sum($column);
    }
}
