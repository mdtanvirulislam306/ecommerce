<?php

namespace Modules\Purchase\Services;

use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchasePayment;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Models\Supplier;

class PurchaseReportService extends Service
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $poSpend = (float) PurchaseOrder::query()
            ->whereNotIn('status', [PurchaseOrderStatus::Draft->value, PurchaseOrderStatus::Cancelled->value])
            ->sum('grand_total');

        $paid = (float) PurchasePayment::query()->sum('amount');
        $returned = (float) PurchaseReturn::query()->sum('grand_total');

        $byStatus = PurchaseOrder::query()
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(grand_total), 0) as amount')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [
                ($row->status instanceof PurchaseOrderStatus ? $row->status->value : (string) $row->status) => [
                    'count' => (int) $row->total,
                    'amount' => number_format((float) $row->amount, 2, '.', ''),
                ],
            ])
            ->all();

        $topSuppliers = DB::table('purchase_orders')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->whereNotIn('purchase_orders.status', ['draft', 'cancelled'])
            ->selectRaw('suppliers.id, suppliers.name, COUNT(purchase_orders.id) as orders_count, SUM(purchase_orders.grand_total) as spend')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('spend')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'orders_count' => (int) $row->orders_count,
                'spend' => number_format((float) $row->spend, 2, '.', ''),
            ])
            ->all();

        $monthly = PurchaseOrder::query()
            ->whereNotIn('status', [PurchaseOrderStatus::Draft->value, PurchaseOrderStatus::Cancelled->value])
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, COUNT(*) as orders_count, SUM(grand_total) as amount")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => $row->period,
                'orders_count' => (int) $row->orders_count,
                'amount' => number_format((float) $row->amount, 2, '.', ''),
            ])
            ->all();

        return [
            'summary' => [
                'suppliers' => Supplier::query()->where('is_active', true)->count(),
                'orders' => PurchaseOrder::query()->count(),
                'spend' => number_format($poSpend, 2, '.', ''),
                'paid' => number_format($paid, 2, '.', ''),
                'outstanding' => number_format(max(0, $poSpend - $paid), 2, '.', ''),
                'returns' => number_format($returned, 2, '.', ''),
                'returns_count' => PurchaseReturn::query()->count(),
            ],
            'by_status' => $byStatus,
            'top_suppliers' => $topSuppliers,
            'monthly' => $monthly,
        ];
    }
}
