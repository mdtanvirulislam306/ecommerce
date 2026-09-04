<?php

namespace Modules\Inventory\Services;

use App\Core\Contracts\StockAvailability;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Enums\StockMovementType;
use Modules\Inventory\Models\StockLevel;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Warehouse;

class StockService extends Service implements StockAvailability
{
    public function __construct(
        private readonly WarehouseService $warehouses,
    ) {}

    public function available(
        int $productId,
        ?int $productVariantId = null,
        ?int $warehouseId = null,
    ): array {
        $query = StockLevel::query()
            ->where('product_id', $productId)
            ->when(
                $productVariantId,
                fn ($q) => $q->where('product_variant_id', $productVariantId),
                fn ($q) => $q->whereNull('product_variant_id'),
            );

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $onHand = (float) (clone $query)->sum('on_hand');
        $reserved = (float) (clone $query)->sum('reserved');

        return [
            'available' => number_format(max(0, $onHand - $reserved), 4, '.', ''),
            'on_hand' => number_format($onHand, 4, '.', ''),
            'reserved' => number_format($reserved, 4, '.', ''),
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'product_variant_id' => $productVariantId,
        ];
    }

    public function canFulfill(
        int $productId,
        int|float $quantity,
        ?int $productVariantId = null,
        ?int $warehouseId = null,
    ): bool {
        $result = $this->available($productId, $productVariantId, $warehouseId);

        return (float) $result['available'] >= (float) $quantity;
    }

    public function fulfill(
        int $productId,
        float $quantity,
        ?int $productVariantId = null,
        ?int $warehouseId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
    ): void {
        $warehouseId ??= $this->warehouses->defaultWarehouse()?->id;

        if ($warehouseId === null) {
            throw ValidationException::withMessages([
                'warehouse' => 'No active warehouse available for fulfillment.',
            ]);
        }

        if (! $this->canFulfill($productId, (int) ceil($quantity), $productVariantId, $warehouseId)) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient available stock to fulfill this line.',
            ]);
        }

        $this->move(
            warehouseId: $warehouseId,
            productId: $productId,
            productVariantId: $productVariantId,
            type: StockMovementType::SaleFulfill,
            quantity: $quantity,
            note: 'Sale fulfillment',
            userId: $userId,
            referenceType: $referenceType,
            referenceId: $referenceId,
        );

        Log::info('inventory.stock.fulfilled', [
            'product_id' => $productId,
            'product_variant_id' => $productVariantId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    public function restock(
        int $productId,
        float $quantity,
        ?int $productVariantId = null,
        ?int $warehouseId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
    ): void {
        $warehouseId ??= $this->warehouses->defaultWarehouse()?->id;

        if ($warehouseId === null) {
            throw ValidationException::withMessages([
                'warehouse' => 'No active warehouse available to restock.',
            ]);
        }

        $this->move(
            warehouseId: $warehouseId,
            productId: $productId,
            productVariantId: $productVariantId,
            type: StockMovementType::ReturnIn,
            quantity: $quantity,
            note: 'Sale cancelled — restock',
            userId: $userId,
            referenceType: $referenceType,
            referenceId: $referenceId,
        );

        Log::info('inventory.stock.restocked', [
            'product_id' => $productId,
            'product_variant_id' => $productVariantId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    public function receive(
        int $productId,
        float $quantity,
        ?int $productVariantId = null,
        ?int $warehouseId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
    ): void {
        $warehouseId ??= $this->warehouses->defaultWarehouse()?->id;

        if ($warehouseId === null) {
            throw ValidationException::withMessages([
                'warehouse' => 'No active warehouse available for purchase receive.',
            ]);
        }

        $this->move(
            warehouseId: $warehouseId,
            productId: $productId,
            productVariantId: $productVariantId,
            type: StockMovementType::PurchaseReceive,
            quantity: $quantity,
            note: 'Purchase receive',
            userId: $userId,
            referenceType: $referenceType,
            referenceId: $referenceId,
        );

        Log::info('inventory.stock.received', [
            'product_id' => $productId,
            'product_variant_id' => $productVariantId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    /**
     * @return array{
     *     warehouses: int,
     *     skus_tracked: int,
     *     on_hand_total: string,
     *     low_stock: int,
     *     out_of_stock: int,
     *     movements_today: int
     * }
     */
    public function overviewStats(): array
    {
        $lowStock = StockLevel::query()
            ->whereColumn('on_hand', '<=', 'reorder_point')
            ->where('on_hand', '>', 0)
            ->count();

        return [
            'warehouses' => Warehouse::query()->where('is_active', true)->count(),
            'skus_tracked' => StockLevel::query()->count(),
            'on_hand_total' => number_format((float) StockLevel::query()->sum('on_hand'), 4, '.', ''),
            'low_stock' => $lowStock,
            'out_of_stock' => StockLevel::query()->where('on_hand', '<=', 0)->count(),
            'movements_today' => StockMovement::query()->whereDate('created_at', today())->count(),
        ];
    }

    public function listLevels(
        ?string $search = null,
        ?int $warehouseId = null,
        ?string $stockFilter = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $query = StockLevel::query()
            ->join('warehouses', 'stock_levels.warehouse_id', '=', 'warehouses.id')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->leftJoin('product_variants', 'stock_levels.product_variant_id', '=', 'product_variants.id')
            ->select([
                'stock_levels.*',
                'warehouses.name as warehouse_name',
                'warehouses.code as warehouse_code',
                'products.name as product_name',
                'products.sku as product_sku',
                'product_variants.sku as variant_sku',
            ])
            ->orderBy('products.name');

        if ($warehouseId) {
            $query->where('stock_levels.warehouse_id', $warehouseId);
        }

        if ($stockFilter === 'low') {
            $query->whereColumn('stock_levels.on_hand', '<=', 'stock_levels.reorder_point')
                ->where('stock_levels.on_hand', '>', 0);
        } elseif ($stockFilter === 'out') {
            $query->where('stock_levels.on_hand', '<=', 0);
        }

        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('product_variants.sku', 'like', "%{$search}%");
            });
        }

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (StockLevel $level) => [
                'id' => $level->id,
                'warehouse_id' => $level->warehouse_id,
                'warehouse_name' => $level->warehouse_name,
                'warehouse_code' => $level->warehouse_code,
                'product_id' => $level->product_id,
                'product_name' => $level->product_name,
                'sku' => $level->variant_sku ?? $level->product_sku,
                'product_variant_id' => $level->product_variant_id,
                'on_hand' => (string) $level->on_hand,
                'reserved' => (string) $level->reserved,
                'available' => $level->available(),
                'reorder_point' => (string) $level->reorder_point,
                'is_low' => (float) $level->on_hand > 0 && (float) $level->on_hand <= (float) $level->reorder_point,
                'is_out' => (float) $level->on_hand <= 0,
            ]);
    }

    public function listMovements(
        ?string $search = null,
        ?int $warehouseId = null,
        ?StockMovementType $type = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $query = StockMovement::query()
            ->join('warehouses', 'stock_movements.warehouse_id', '=', 'warehouses.id')
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->leftJoin('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
            ->select([
                'stock_movements.*',
                'warehouses.name as warehouse_name',
                'products.name as product_name',
                'products.sku as product_sku',
                'product_variants.sku as variant_sku',
                'users.name as created_by_name',
            ])
            ->orderByDesc('stock_movements.created_at');

        if ($warehouseId) {
            $query->where('stock_movements.warehouse_id', $warehouseId);
        }

        if ($type) {
            $query->where('stock_movements.type', $type->value);
        }

        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('product_variants.sku', 'like', "%{$search}%")
                    ->orWhere('stock_movements.note', 'like', "%{$search}%");
            });
        }

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'warehouse_name' => $movement->warehouse_name,
                'product_name' => $movement->product_name,
                'sku' => $movement->variant_sku ?? $movement->product_sku,
                'type' => $movement->type->value,
                'type_label' => $movement->type->label(),
                'quantity' => (string) $movement->quantity,
                'quantity_before' => (string) $movement->quantity_before,
                'quantity_after' => (string) $movement->quantity_after,
                'note' => $movement->note,
                'created_by_name' => $movement->created_by_name,
                'created_at' => $movement->created_at?->toIso8601String(),
            ]);
    }

    /**
     * @param  array{
     *     warehouse_id: int,
     *     product_id: int,
     *     product_variant_id?: int|null,
     *     direction: string,
     *     quantity: float|int|string,
     *     note?: string|null,
     *     reorder_point?: float|int|string|null
     * }  $data
     */
    public function adjust(array $data, ?int $userId = null): StockMovement
    {
        $direction = $data['direction'] ?? 'in';
        $type = $direction === 'out'
            ? StockMovementType::AdjustmentOut
            : StockMovementType::AdjustmentIn;

        return $this->move(
            warehouseId: (int) $data['warehouse_id'],
            productId: (int) $data['product_id'],
            productVariantId: ! empty($data['product_variant_id']) ? (int) $data['product_variant_id'] : null,
            type: $type,
            quantity: (float) $data['quantity'],
            note: $data['note'] ?? null,
            userId: $userId,
            reorderPoint: array_key_exists('reorder_point', $data) ? (float) $data['reorder_point'] : null,
        );
    }

    public function move(
        int $warehouseId,
        int $productId,
        ?int $productVariantId,
        StockMovementType $type,
        float $quantity,
        ?string $note = null,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?float $reorderPoint = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }

        $this->assertProductExists($productId, $productVariantId);

        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $productVariantId,
            $type,
            $quantity,
            $note,
            $userId,
            $referenceType,
            $referenceId,
            $reorderPoint,
        ) {
            $level = StockLevel::query()->firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'product_variant_id' => $productVariantId,
                ],
                [
                    'on_hand' => 0,
                    'reserved' => 0,
                    'reorder_point' => 0,
                ],
            );

            $level = StockLevel::query()->whereKey($level->id)->lockForUpdate()->firstOrFail();

            $before = (float) $level->on_hand;
            $delta = (float) $type->signedQuantity($quantity);
            $after = $before + $delta;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock for this movement.',
                ]);
            }

            $level->on_hand = number_format($after, 4, '.', '');

            if ($reorderPoint !== null) {
                $level->reorder_point = number_format(max(0, $reorderPoint), 4, '.', '');
            }

            $level->save();

            return StockMovement::query()->create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $productVariantId,
                'type' => $type,
                'quantity' => number_format($delta, 4, '.', ''),
                'quantity_before' => number_format($before, 4, '.', ''),
                'quantity_after' => number_format($after, 4, '.', ''),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $note,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * @return list<array{id: int, name: string, sku: ?string, type: string}>
     */
    public function searchProducts(?string $search = null, int $limit = 40): array
    {
        $query = DB::table('products')
            ->select(['id', 'name', 'sku', 'type'])
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->limit($limit);

        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query->get()->map(fn ($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'sku' => $row->sku,
            'type' => $row->type,
        ])->all();
    }

    /**
     * @return list<array{id: int, sku: string, name: ?string}>
     */
    public function variantsForProduct(int $productId): array
    {
        return DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'sku', 'name'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'sku' => $row->sku,
                'name' => $row->name,
            ])
            ->all();
    }

    private function assertProductExists(int $productId, ?int $productVariantId): void
    {
        $product = DB::table('products')->where('id', $productId)->first();

        if ($product === null || $product->status === 'archived') {
            throw ValidationException::withMessages([
                'product_id' => 'Product not found or archived.',
            ]);
        }

        if ($productVariantId) {
            $variant = DB::table('product_variants')
                ->where('id', $productVariantId)
                ->where('product_id', $productId)
                ->first();

            if ($variant === null) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Variant does not belong to this product.',
                ]);
            }
        }
    }
}
