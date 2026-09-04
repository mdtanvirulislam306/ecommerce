<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Commerce\Models\Promotion;

class PromotionService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25, ?string $type = null): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Promotion::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Promotion
    {
        return Promotion::query()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'value' => $data['value'] ?? 0,
            'buy_qty' => $data['buy_qty'] ?? null,
            'get_qty' => $data['get_qty'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Promotion $promotion, array $data): Promotion
    {
        $promotion->update([
            'name' => $data['name'] ?? $promotion->name,
            'type' => $data['type'] ?? $promotion->type,
            'value' => $data['value'] ?? $promotion->value,
            'buy_qty' => array_key_exists('buy_qty', $data) ? $data['buy_qty'] : $promotion->buy_qty,
            'get_qty' => array_key_exists('get_qty', $data) ? $data['get_qty'] : $promotion->get_qty,
            'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $promotion->starts_at,
            'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $promotion->ends_at,
            'is_active' => $data['is_active'] ?? $promotion->is_active,
            'description' => array_key_exists('description', $data) ? $data['description'] : $promotion->description,
        ]);

        return $promotion->fresh();
    }

    public function delete(Promotion $promotion): void
    {
        $promotion->delete();
    }

    public function overviewStats(): array
    {
        return [
            'promotions' => Promotion::query()->count(),
            'active_promotions' => Promotion::query()->where('is_active', true)->count(),
            'discount_rules' => Promotion::query()->where('type', 'discount_rule')->count(),
            'buy_x_get_y' => Promotion::query()->where('type', 'buy_x_get_y')->count(),
            'free_shipping' => Promotion::query()->where('type', 'free_shipping')->count(),
        ];
    }
}
