<?php

namespace Modules\Pos\Services;

use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Modules\Pos\Enums\PosOrderStatus;
use Modules\Pos\Enums\PosSessionStatus;
use Modules\Pos\Models\PosCashMovement;
use Modules\Pos\Models\PosOrder;
use Modules\Pos\Models\PosReturn;
use Modules\Pos\Models\PosSession;

class PosReportService extends Service
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $today = now()->toDateString();

        $revenueToday = (float) PosOrder::query()
            ->where('status', PosOrderStatus::Completed)
            ->whereDate('completed_at', $today)
            ->sum('grand_total');

        $returnsToday = (float) PosReturn::query()
            ->whereDate('returned_at', $today)
            ->sum('grand_total');

        return [
            'summary' => [
                'open_sessions' => PosSession::query()->where('status', PosSessionStatus::Open)->count(),
                'orders_today' => PosOrder::query()->whereDate('completed_at', $today)->where('status', PosOrderStatus::Completed)->count(),
                'revenue_today' => number_format($revenueToday, 2, '.', ''),
                'returns_today' => number_format($returnsToday, 2, '.', ''),
                'cash_in_today' => number_format((float) PosCashMovement::query()->where('type', 'in')->whereDate('created_at', $today)->sum('amount'), 2, '.', ''),
                'cash_out_today' => number_format((float) PosCashMovement::query()->where('type', 'out')->whereDate('created_at', $today)->sum('amount'), 2, '.', ''),
            ],
            'monthly' => PosOrder::query()
                ->where('status', PosOrderStatus::Completed)
                ->where('completed_at', '>=', now()->subMonths(5)->startOfMonth())
                ->selectRaw("DATE_FORMAT(completed_at, '%Y-%m') as period, COUNT(*) as orders_count, SUM(grand_total) as amount")
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(fn ($row) => [
                    'period' => $row->period,
                    'orders_count' => (int) $row->orders_count,
                    'amount' => number_format((float) $row->amount, 2, '.', ''),
                ])
                ->all(),
            'top_registers' => DB::table('pos_orders')
                ->join('pos_registers', 'pos_registers.id', '=', 'pos_orders.pos_register_id')
                ->where('pos_orders.status', 'completed')
                ->selectRaw('pos_registers.name, COUNT(pos_orders.id) as orders_count, SUM(pos_orders.grand_total) as amount')
                ->groupBy('pos_registers.name')
                ->orderByDesc('amount')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'name' => $row->name,
                    'orders_count' => (int) $row->orders_count,
                    'amount' => number_format((float) $row->amount, 2, '.', ''),
                ])
                ->all(),
        ];
    }
}
