<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Commerce\Enums\PriceChangeAction;
use Modules\Commerce\Models\PriceHistory;
use Modules\Commerce\Models\PriceList;
use Modules\Commerce\Models\PriceListItem;

class PriceHistoryService extends Service
{
    public function record(
        PriceList $priceList,
        PriceChangeAction $action,
        ?PriceListItem $item = null,
        ?string $oldPrice = null,
        ?string $newPrice = null,
        ?int $productId = null,
        ?int $productVariantId = null,
        int $minQuantity = 1,
        ?int $changedByUserId = null,
    ): PriceHistory {
        return PriceHistory::query()->create([
            'price_list_id' => $priceList->id,
            'price_list_item_id' => $item?->id,
            'product_id' => $productId ?? $item?->product_id,
            'product_variant_id' => $productVariantId ?? $item?->product_variant_id,
            'action' => $action,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'min_quantity' => $item?->min_quantity ?? $minQuantity,
            'currency' => $priceList->currency,
            'changed_by' => $changedByUserId,
        ]);
    }

    public function listPaginated(
        ?string $search = null,
        ?int $priceListId = null,
        ?PriceChangeAction $action = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $query = PriceHistory::query()
            ->join('price_lists', 'price_history.price_list_id', '=', 'price_lists.id')
            ->leftJoin('users', 'price_history.changed_by', '=', 'users.id')
            ->leftJoin('products', 'price_history.product_id', '=', 'products.id')
            ->leftJoin('product_variants', 'price_history.product_variant_id', '=', 'product_variants.id')
            ->select([
                'price_history.*',
                'price_lists.name as price_list_name',
                'price_lists.code as price_list_code',
                'users.name as changed_by_name',
                'products.name as product_name',
                'products.sku as product_sku',
                'product_variants.sku as variant_sku',
                'product_variants.name as variant_name',
            ])
            ->orderByDesc('price_history.created_at');

        if ($priceListId) {
            $query->where('price_history.price_list_id', $priceListId);
        }

        if ($action) {
            $query->where('price_history.action', $action->value);
        }

        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('product_variants.sku', 'like', "%{$search}%")
                    ->orWhere('price_lists.name', 'like', "%{$search}%");
            });
        }

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PriceHistory $row) => $this->formatForList($row));
    }

    public function backfillFromExistingItems(): int
    {
        if (PriceHistory::query()->exists()) {
            return 0;
        }

        $count = 0;

        PriceListItem::query()
            ->with('priceList')
            ->orderBy('id')
            ->chunkById(100, function ($items) use (&$count) {
                foreach ($items as $item) {
                    if ($item->priceList === null) {
                        continue;
                    }

                    $this->record(
                        priceList: $item->priceList,
                        action: PriceChangeAction::Created,
                        item: $item,
                        newPrice: (string) $item->price,
                        changedByUserId: null,
                    );

                    $count++;
                }
            });

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatForList(PriceHistory $row): array
    {
        $productLabel = $row->product_name;

        if ($row->variant_sku) {
            $productLabel = trim(($row->product_name ?? '').' — '.$row->variant_sku);
        }

        return [
            'id' => $row->id,
            'price_list_id' => $row->price_list_id,
            'price_list_name' => $row->price_list_name,
            'price_list_code' => $row->price_list_code,
            'product_label' => $productLabel ?: '—',
            'product_sku' => $row->variant_sku ?? $row->product_sku,
            'action' => $row->action->value,
            'action_label' => $row->action->label(),
            'old_price' => $row->old_price !== null ? (string) $row->old_price : null,
            'new_price' => $row->new_price !== null ? (string) $row->new_price : null,
            'min_quantity' => $row->min_quantity,
            'currency' => $row->currency,
            'changed_by_name' => $row->changed_by_name,
            'created_at' => $row->created_at?->toIso8601String(),
        ];
    }
}
