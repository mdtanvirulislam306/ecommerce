<?php

namespace Modules\Inventory\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Enums\StockMovementType;
use Modules\Inventory\Enums\StockTransferStatus;
use Modules\Inventory\Models\StockTransfer;
use Modules\Inventory\Models\Warehouse;

class StockTransferService extends Service
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return StockTransfer::query()
            ->with(['fromWarehouse:id,name,code', 'toWarehouse:id,name,code'])
            ->withCount('items')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (StockTransfer $transfer) => $this->formatForList($transfer));
    }

    /**
     * @param  array{
     *     from_warehouse_id: int,
     *     to_warehouse_id: int,
     *     notes?: string|null,
     *     items: list<array{product_id: int, product_variant_id?: int|null, quantity: float|int|string}>
     * }  $data
     */
    public function create(array $data, ?int $userId = null): StockTransfer
    {
        $fromId = (int) $data['from_warehouse_id'];
        $toId = (int) $data['to_warehouse_id'];

        if ($fromId === $toId) {
            throw ValidationException::withMessages([
                'to_warehouse_id' => 'Destination warehouse must differ from the source.',
            ]);
        }

        $this->assertActiveWarehouse($fromId, 'from_warehouse_id');
        $this->assertActiveWarehouse($toId, 'to_warehouse_id');

        $items = $data['items'] ?? [];

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one transfer line.',
            ]);
        }

        return DB::transaction(function () use ($data, $fromId, $toId, $items, $userId) {
            foreach ($items as $index => $item) {
                $productId = (int) $item['product_id'];
                $variantId = ! empty($item['product_variant_id']) ? (int) $item['product_variant_id'] : null;
                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => 'Quantity must be greater than zero.',
                    ]);
                }

                if (! $this->stock->canFulfill($productId, $quantity, $variantId, $fromId)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => 'Insufficient available stock at the source warehouse.',
                    ]);
                }
            }

            $transfer = StockTransfer::query()->create([
                'number' => $this->nextNumber(),
                'from_warehouse_id' => $fromId,
                'to_warehouse_id' => $toId,
                'status' => StockTransferStatus::Completed,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($items as $index => $item) {
                $productId = (int) $item['product_id'];
                $variantId = ! empty($item['product_variant_id']) ? (int) $item['product_variant_id'] : null;
                $quantity = (float) $item['quantity'];

                $transfer->items()->create([
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'quantity' => number_format($quantity, 4, '.', ''),
                    'sort_order' => $index,
                ]);

                $this->stock->move(
                    warehouseId: $fromId,
                    productId: $productId,
                    productVariantId: $variantId,
                    type: StockMovementType::TransferOut,
                    quantity: $quantity,
                    note: "Transfer {$transfer->number} → warehouse #{$toId}",
                    userId: $userId,
                    referenceType: StockTransfer::class,
                    referenceId: $transfer->id,
                );

                $this->stock->move(
                    warehouseId: $toId,
                    productId: $productId,
                    productVariantId: $variantId,
                    type: StockMovementType::TransferIn,
                    quantity: $quantity,
                    note: "Transfer {$transfer->number} ← warehouse #{$fromId}",
                    userId: $userId,
                    referenceType: StockTransfer::class,
                    referenceId: $transfer->id,
                );
            }

            Log::info('inventory.stock_transfer.completed', [
                'transfer_id' => $transfer->id,
                'number' => $transfer->number,
                'from_warehouse_id' => $fromId,
                'to_warehouse_id' => $toId,
                'lines' => count($items),
            ]);

            return $transfer->fresh(['items', 'fromWarehouse', 'toWarehouse']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForList(StockTransfer $transfer): array
    {
        $transfer->loadMissing(['fromWarehouse', 'toWarehouse']);

        return [
            'id' => $transfer->id,
            'number' => $transfer->number,
            'status' => $transfer->status->value,
            'status_label' => $transfer->status->label(),
            'from_warehouse' => $transfer->fromWarehouse?->name,
            'from_warehouse_code' => $transfer->fromWarehouse?->code,
            'to_warehouse' => $transfer->toWarehouse?->name,
            'to_warehouse_code' => $transfer->toWarehouse?->code,
            'items_count' => $transfer->items_count ?? $transfer->items()->count(),
            'notes' => $transfer->notes,
            'created_at' => $transfer->created_at?->toIso8601String(),
        ];
    }

    private function nextNumber(): string
    {
        $seq = StockTransfer::query()->lockForUpdate()->count() + 1;

        return 'ST-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function assertActiveWarehouse(int $warehouseId, string $field): void
    {
        $exists = Warehouse::query()
            ->whereKey($warehouseId)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                $field => 'Warehouse not found or inactive.',
            ]);
        }
    }
}
