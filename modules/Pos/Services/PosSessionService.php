<?php

namespace Modules\Pos\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pos\Enums\PosSessionStatus;
use Modules\Pos\Models\PosCashMovement;
use Modules\Pos\Models\PosSession;

class PosSessionService extends Service
{
    public function listOpen(?string $search = null, int $perPage = 25, ?string $dateFrom = null, ?string $dateTo = null): LengthAwarePaginator
    {
        return $this->listPaginated($search, PosSessionStatus::Open, $perPage, $dateFrom, $dateTo);
    }

    public function listHistory(?string $search = null, int $perPage = 25, ?string $dateFrom = null, ?string $dateTo = null): LengthAwarePaginator
    {
        return $this->listPaginated($search, PosSessionStatus::Closed, $perPage, $dateFrom, $dateTo);
    }

    public function listPaginated(
        ?string $search = null,
        ?PosSessionStatus $status = null,
        int $perPage = 25,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
        $dateColumn = $status === PosSessionStatus::Closed ? 'closed_at' : 'opened_at';

        return PosSession::query()
            ->with(['register:id,name,code', 'openedByUser:id,name'])
            ->withCount('orders')
            ->when($status, fn ($query, $status) => $query->where('status', $status->value))
            ->when($search, fn ($query, $search) => $query->whereHas('register', fn ($register) => $register
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")))
            ->when($dateFrom, fn ($query, $dateFrom) => $query->whereDate($dateColumn, '>=', $dateFrom))
            ->when($dateTo, fn ($query, $dateTo) => $query->whereDate($dateColumn, '<=', $dateTo))
            ->orderByDesc($dateColumn)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PosSession $session) => $this->format($session));
    }

    /**
     * @return list<array{id: int, label: string, opening_cash: string}>
     */
    public function openSessionOptions(): array
    {
        return PosSession::query()
            ->with('register:id,name,code')
            ->where('status', PosSessionStatus::Open)
            ->orderByDesc('opened_at')
            ->get()
            ->map(fn (PosSession $session) => [
                'id' => $session->id,
                'label' => ($session->register?->name ?? 'Register').' #'.$session->id,
                'opening_cash' => (string) $session->opening_cash,
            ])
            ->all();
    }

    public function listCashMovements(
        ?int $sessionId = null,
        int $perPage = 25,
        ?string $type = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
        $type = in_array($type, ['in', 'out'], true) ? $type : null;

        return PosCashMovement::query()
            ->with(['session.register:id,name'])
            ->when($sessionId, fn ($query, $sessionId) => $query->where('pos_session_id', $sessionId))
            ->when($type, fn ($query, $type) => $query->where('type', $type))
            ->when($dateFrom, fn ($query, $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query, $dateTo) => $query->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PosCashMovement $movement) => [
                'id' => $movement->id,
                'pos_session_id' => $movement->pos_session_id,
                'session_label' => $movement->session?->register?->name.' #'.$movement->pos_session_id,
                'type' => $movement->type,
                'amount' => (string) $movement->amount,
                'reason' => $movement->reason,
                'notes' => $movement->notes,
                'created_at' => $movement->created_at?->toIso8601String(),
            ]);
    }

    /**
     * @param  array{pos_session_id: int, type: string, amount: float|int|string, reason?: string|null, notes?: string|null}  $data
     */
    public function recordCashMovement(array $data, ?int $userId = null): PosCashMovement
    {
        return DB::transaction(function () use ($data, $userId) {
            $session = PosSession::query()->whereKey($data['pos_session_id'])->lockForUpdate()->firstOrFail();

            if ($session->status !== PosSessionStatus::Open) {
                throw ValidationException::withMessages([
                    'pos_session_id' => 'Cash movements are only allowed on open sessions.',
                ]);
            }

            $type = $data['type'] === 'out' ? 'out' : 'in';
            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount must be greater than zero.',
                ]);
            }

            return PosCashMovement::query()->create([
                'pos_session_id' => $session->id,
                'type' => $type,
                'amount' => number_format($amount, 4, '.', ''),
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function format(PosSession $session): array
    {
        $cashIn = (float) $session->cashMovements()->where('type', 'in')->sum('amount');
        $cashOut = (float) $session->cashMovements()->where('type', 'out')->sum('amount');
        $sales = (float) $session->orders()->where('status', 'completed')->sum('grand_total');

        return [
            'id' => $session->id,
            'register_name' => $session->register?->name,
            'register_code' => $session->register?->code,
            'status' => $session->status->value,
            'opened_by' => $session->openedByUser?->name,
            'opening_cash' => (string) $session->opening_cash,
            'closing_cash' => $session->closing_cash !== null ? (string) $session->closing_cash : null,
            'expected_cash' => $session->expected_cash !== null ? (string) $session->expected_cash : number_format(
                (float) $session->opening_cash + $sales + $cashIn - $cashOut,
                4,
                '.',
                '',
            ),
            'cash_in' => number_format($cashIn, 4, '.', ''),
            'cash_out' => number_format($cashOut, 4, '.', ''),
            'sales_total' => number_format($sales, 4, '.', ''),
            'orders_count' => $session->orders_count ?? $session->orders()->count(),
            'opened_at' => $session->opened_at?->toIso8601String(),
            'closed_at' => $session->closed_at?->toIso8601String(),
        ];
    }
}
