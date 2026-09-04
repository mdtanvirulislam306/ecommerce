<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Commerce\Enums\PriceChangeAction;
use Modules\Commerce\Models\PriceList;
use Modules\Commerce\Models\PriceListItem;

class PriceListService extends Service
{
    public function __construct(
        private readonly PriceHistoryService $priceHistory,
    ) {}

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return PriceList::query()
            ->withCount('items')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): PriceList
    {
        return DB::transaction(function () use ($data) {
            if ($data['is_default'] ?? false) {
                PriceList::query()->update(['is_default' => false]);
            }

            return PriceList::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'currency' => $data['currency'] ?? 'BDT',
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    public function update(PriceList $priceList, array $data): PriceList
    {
        return DB::transaction(function () use ($priceList, $data) {
            if ($data['is_default'] ?? false) {
                PriceList::query()->where('id', '!=', $priceList->id)->update(['is_default' => false]);
            }

            $priceList->update([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? $priceList->description,
                'currency' => $data['currency'] ?? $priceList->currency,
                'is_active' => $data['is_active'] ?? $priceList->is_active,
                'is_default' => $data['is_default'] ?? $priceList->is_default,
                'sort_order' => $data['sort_order'] ?? $priceList->sort_order,
            ]);

            return $priceList->fresh();
        });
    }

    public function delete(PriceList $priceList): void
    {
        DB::transaction(fn () => $priceList->delete());
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForDetail(PriceList $priceList): array
    {
        $priceList->loadCount('items');
        $items = $priceList->items()->orderBy('min_quantity')->get();
        $labels = $this->resolveItemLabels($items);

        return [
            'id' => $priceList->id,
            'name' => $priceList->name,
            'code' => $priceList->code,
            'description' => $priceList->description,
            'currency' => $priceList->currency,
            'is_active' => $priceList->is_active,
            'is_default' => $priceList->is_default,
            'sort_order' => $priceList->sort_order,
            'items_count' => $priceList->items_count,
            'items' => $items->map(fn (PriceListItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'label' => $labels[$item->id] ?? '—',
                'sku' => $labels['sku_'.$item->id] ?? null,
                'price' => $item->price,
                'min_quantity' => $item->min_quantity,
            ])->all(),
        ];
    }

    public function addItem(PriceList $priceList, array $data, ?int $changedByUserId = null): PriceListItem
    {
        return DB::transaction(function () use ($priceList, $data, $changedByUserId) {
            $item = PriceListItem::query()->create([
                'price_list_id' => $priceList->id,
                'product_id' => $data['product_variant_id'] ? null : $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'price' => $data['price'],
                'min_quantity' => $data['min_quantity'] ?? 1,
            ]);

            $this->priceHistory->record(
                priceList: $priceList,
                action: PriceChangeAction::Created,
                item: $item,
                newPrice: (string) $item->price,
                productId: $data['product_id'] ?? null,
                productVariantId: $data['product_variant_id'] ?? null,
                changedByUserId: $changedByUserId,
            );

            return $item;
        });
    }

    public function updateItem(PriceListItem $item, array $data, ?int $changedByUserId = null): PriceListItem
    {
        return DB::transaction(function () use ($item, $data, $changedByUserId) {
            $priceList = $item->priceList;

            if ($priceList === null) {
                throw new \RuntimeException('Price list not found for item.');
            }

            $oldPrice = (string) $item->price;
            $oldMinQuantity = $item->min_quantity;

            $item->update([
                'price' => $data['price'],
                'min_quantity' => $data['min_quantity'],
            ]);

            if ($oldPrice !== (string) $item->price || $oldMinQuantity !== $item->min_quantity) {
                $this->priceHistory->record(
                    priceList: $priceList,
                    action: PriceChangeAction::Updated,
                    item: $item,
                    oldPrice: $oldPrice,
                    newPrice: (string) $item->price,
                    changedByUserId: $changedByUserId,
                );
            }

            return $item->fresh();
        });
    }

    public function deleteItem(PriceListItem $item, ?int $changedByUserId = null): void
    {
        DB::transaction(function () use ($item, $changedByUserId) {
            $priceList = $item->priceList;

            if ($priceList) {
                $this->priceHistory->record(
                    priceList: $priceList,
                    action: PriceChangeAction::Deleted,
                    item: $item,
                    oldPrice: (string) $item->price,
                    changedByUserId: $changedByUserId,
                );
            }

            $item->delete();
        });
    }

    /**
     * @return list<array{id: int, name: string, sku: string|null, type: string}>
     */
    public function searchProducts(?string $search = null, int $limit = 20): array
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
     * @return list<array{id: int, sku: string, name: string|null}>
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

    /**
     * @param  Collection<int, PriceListItem>  $items
     * @return array<int|string, string>
     */
    private function resolveItemLabels(Collection $items): array
    {
        $labels = [];
        $productIds = $items->pluck('product_id')->filter()->unique()->all();
        $variantIds = $items->pluck('product_variant_id')->filter()->unique()->all();

        $products = $productIds
            ? DB::table('products')->whereIn('id', $productIds)->pluck('name', 'id')
            : collect();

        $variants = $variantIds
            ? DB::table('product_variants')->whereIn('id', $variantIds)->get(['id', 'sku', 'name', 'product_id'])
            : collect();

        $variantProductIds = $variants->pluck('product_id')->unique()->filter()->all();
        $variantProducts = $variantProductIds
            ? DB::table('products')->whereIn('id', $variantProductIds)->pluck('name', 'id')
            : collect();

        foreach ($items as $item) {
            if ($item->product_variant_id) {
                $variant = $variants->firstWhere('id', $item->product_variant_id);
                $productName = $variant ? ($variantProducts[$variant->product_id] ?? '') : '';
                $labels[$item->id] = trim("{$productName} — {$variant?->sku}");
                $labels['sku_'.$item->id] = $variant?->sku;
            } else {
                $labels[$item->id] = $products[$item->product_id] ?? 'Product #'.$item->product_id;
                $labels['sku_'.$item->id] = DB::table('products')->where('id', $item->product_id)->value('sku');
            }
        }

        return $labels;
    }
}
