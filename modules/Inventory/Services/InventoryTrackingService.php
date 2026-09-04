<?php

namespace Modules\Inventory\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Models\StockLevel;

class InventoryTrackingService extends Service
{
    public function listBatches(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return InventoryBatch::query()
            ->when($search, fn ($q, $s) => $q->where(function ($inner) use ($s) {
                $inner->where('batch_number', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (InventoryBatch $batch) => [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'product_id' => $batch->product_id,
                'product_name' => DB::table('products')->where('id', $batch->product_id)->value('name'),
                'warehouse_id' => $batch->warehouse_id,
                'warehouse_name' => DB::table('warehouses')->where('id', $batch->warehouse_id)->value('name'),
                'quantity' => $batch->quantity,
                'manufactured_at' => $batch->manufactured_at?->toDateString(),
                'expires_at' => $batch->expires_at?->toDateString(),
                'notes' => $batch->notes,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createBatch(array $data): InventoryBatch
    {
        return InventoryBatch::query()->create([
            'batch_number' => $data['batch_number'],
            'product_id' => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'],
            'quantity' => $data['quantity'] ?? 0,
            'manufactured_at' => $data['manufactured_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBatch(InventoryBatch $batch, array $data): InventoryBatch
    {
        $batch->update([
            'batch_number' => $data['batch_number'] ?? $batch->batch_number,
            'product_id' => $data['product_id'] ?? $batch->product_id,
            'product_variant_id' => $data['product_variant_id'] ?? $batch->product_variant_id,
            'warehouse_id' => $data['warehouse_id'] ?? $batch->warehouse_id,
            'quantity' => $data['quantity'] ?? $batch->quantity,
            'manufactured_at' => $data['manufactured_at'] ?? $batch->manufactured_at,
            'expires_at' => $data['expires_at'] ?? $batch->expires_at,
            'notes' => $data['notes'] ?? $batch->notes,
        ]);

        return $batch->fresh();
    }

    public function deleteBatch(InventoryBatch $batch): void
    {
        $batch->delete();
    }

    public function listSerials(?string $search = null, ?string $status = null, int $perPage = 25): LengthAwarePaginator
    {
        return InventorySerialNumber::query()
            ->when($status, fn ($q, $status) => $q->where('status', $status))
            ->when($search, fn ($q, $s) => $q->where(function ($inner) use ($s) {
                $inner->where('serial_number', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (InventorySerialNumber $row) => [
                'id' => $row->id,
                'serial_number' => $row->serial_number,
                'product_id' => $row->product_id,
                'product_name' => DB::table('products')->where('id', $row->product_id)->value('name'),
                'warehouse_id' => $row->warehouse_id,
                'warehouse_name' => $row->warehouse_id
                    ? DB::table('warehouses')->where('id', $row->warehouse_id)->value('name')
                    : null,
                'status' => $row->status,
                'notes' => $row->notes,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSerial(array $data): InventorySerialNumber
    {
        return InventorySerialNumber::query()->create([
            'serial_number' => $data['serial_number'],
            'product_id' => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'status' => $data['status'] ?? 'in_stock',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSerial(InventorySerialNumber $serial, array $data): InventorySerialNumber
    {
        $serial->update([
            'serial_number' => $data['serial_number'] ?? $serial->serial_number,
            'product_id' => $data['product_id'] ?? $serial->product_id,
            'product_variant_id' => $data['product_variant_id'] ?? $serial->product_variant_id,
            'warehouse_id' => $data['warehouse_id'] ?? $serial->warehouse_id,
            'status' => $data['status'] ?? $serial->status,
            'notes' => $data['notes'] ?? $serial->notes,
        ]);

        return $serial->fresh();
    }

    public function deleteSerial(InventorySerialNumber $serial): void
    {
        $serial->delete();
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total_value: string, currency: string}
     */
    public function valuation(?int $warehouseId = null): array
    {
        $levels = StockLevel::query()
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->get();

        $retailListId = DB::table('price_lists')->where('code', 'retail')->value('id')
            ?? DB::table('price_lists')->where('is_default', true)->value('id');

        $rows = [];
        $total = 0.0;

        foreach ($levels as $level) {
            $price = 0.0;
            if ($retailListId) {
                $price = (float) (DB::table('price_list_items')
                    ->where('price_list_id', $retailListId)
                    ->where('product_id', $level->product_id)
                    ->whereNull('product_variant_id')
                    ->where('min_quantity', 1)
                    ->value('price') ?? 0);
            }

            $qty = (float) $level->on_hand;
            $value = $qty * $price;
            $total += $value;

            $rows[] = [
                'product_id' => $level->product_id,
                'product_name' => DB::table('products')->where('id', $level->product_id)->value('name'),
                'warehouse_id' => $level->warehouse_id,
                'warehouse_name' => DB::table('warehouses')->where('id', $level->warehouse_id)->value('name'),
                'on_hand' => number_format($qty, 4, '.', ''),
                'unit_cost' => number_format($price, 4, '.', ''),
                'value' => number_format($value, 2, '.', ''),
            ];
        }

        return [
            'rows' => $rows,
            'total_value' => number_format($total, 2, '.', ''),
            'currency' => 'BDT',
        ];
    }

    /**
     * @return array{batches: int, serials_in_stock: int, low_stock: int, on_hand_total: string}
     */
    public function reportStats(): array
    {
        return [
            'batches' => InventoryBatch::query()->count(),
            'serials_in_stock' => InventorySerialNumber::query()->where('status', 'in_stock')->count(),
            'low_stock' => StockLevel::query()->whereRaw('on_hand - reserved <= 5')->count(),
            'on_hand_total' => number_format((float) StockLevel::query()->sum('on_hand'), 4, '.', ''),
        ];
    }
}
