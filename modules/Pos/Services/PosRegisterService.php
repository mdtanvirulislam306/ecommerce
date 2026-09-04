<?php

namespace Modules\Pos\Services;

use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pos\Enums\PosSessionStatus;
use Modules\Pos\Models\PosRegister;
use Modules\Pos\Models\PosSession;

class PosRegisterService extends Service
{
    public function ensureDefault(): PosRegister
    {
        $existing = PosRegister::query()->where('is_default', true)->where('is_active', true)->first()
            ?? PosRegister::query()->where('is_active', true)->orderBy('sort_order')->first();

        if ($existing) {
            return $existing;
        }

        $warehouseId = DB::table('warehouses')->where('is_default', true)->value('id')
            ?? DB::table('warehouses')->orderBy('id')->value('id');

        return PosRegister::query()->create([
            'name' => 'Main Register',
            'code' => 'REG-MAIN',
            'warehouse_id' => $warehouseId,
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        $this->ensureDefault();

        return PosRegister::query()
            ->with(['openSession'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (PosRegister $register) => $this->format($register))
            ->all();
    }

    /**
     * @param  array{name: string, code: string, warehouse_id?: int|null, is_default?: bool, is_active?: bool, sort_order?: int}  $data
     */
    public function create(array $data): PosRegister
    {
        return DB::transaction(function () use ($data) {
            if ($data['is_default'] ?? false) {
                PosRegister::query()->update(['is_default' => false]);
            }

            return PosRegister::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'is_default' => $data['is_default'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PosRegister $register, array $data): PosRegister
    {
        return DB::transaction(function () use ($register, $data) {
            if ($data['is_default'] ?? false) {
                PosRegister::query()->whereKeyNot($register->id)->update(['is_default' => false]);
            }

            $register->update([
                'name' => $data['name'],
                'code' => $data['code'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'is_default' => $data['is_default'] ?? $register->is_default,
                'is_active' => $data['is_active'] ?? $register->is_active,
                'sort_order' => $data['sort_order'] ?? $register->sort_order,
            ]);

            return $register->fresh();
        });
    }

    public function openSession(PosRegister $register, float $openingCash = 0, ?int $userId = null): PosSession
    {
        if ($register->openSession()->exists()) {
            throw ValidationException::withMessages([
                'session' => 'This register already has an open session.',
            ]);
        }

        return PosSession::query()->create([
            'pos_register_id' => $register->id,
            'opened_by' => $userId,
            'status' => PosSessionStatus::Open,
            'opening_cash' => number_format($openingCash, 4, '.', ''),
            'opened_at' => now(),
        ]);
    }

    public function closeSession(PosSession $session, float $closingCash, ?int $userId = null): PosSession
    {
        return DB::transaction(function () use ($session, $closingCash, $userId) {
            $session = PosSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($session->status !== PosSessionStatus::Open) {
                throw ValidationException::withMessages([
                    'session' => 'Session is already closed.',
                ]);
            }

            $salesCash = (float) $session->orders()
                ->where('status', 'completed')
                ->sum('grand_total');

            $cashIn = (float) $session->cashMovements()->where('type', 'in')->sum('amount');
            $cashOut = (float) $session->cashMovements()->where('type', 'out')->sum('amount');

            $expected = (float) $session->opening_cash + $salesCash + $cashIn - $cashOut;

            $session->update([
                'status' => PosSessionStatus::Closed,
                'closing_cash' => number_format($closingCash, 4, '.', ''),
                'expected_cash' => number_format($expected, 4, '.', ''),
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);

            return $session->fresh();
        });
    }

    public function currentOpenSession(?int $registerId = null): ?PosSession
    {
        $register = $registerId
            ? PosRegister::query()->findOrFail($registerId)
            : $this->ensureDefault();

        return $register->openSession;
    }

    /**
     * @return array<string, mixed>
     */
    public function format(PosRegister $register): array
    {
        $open = $register->relationLoaded('openSession')
            ? $register->openSession
            : $register->openSession()->first();

        return [
            'id' => $register->id,
            'name' => $register->name,
            'code' => $register->code,
            'warehouse_id' => $register->warehouse_id,
            'is_default' => $register->is_default,
            'is_active' => $register->is_active,
            'has_open_session' => $open !== null,
            'open_session_id' => $open?->id,
            'opening_cash' => $open ? (string) $open->opening_cash : null,
        ];
    }
}
