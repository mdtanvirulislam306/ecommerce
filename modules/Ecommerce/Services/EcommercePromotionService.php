<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\EcommercePromotion;

class EcommercePromotionService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return EcommercePromotion::query()
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): EcommercePromotion
    {
        return DB::transaction(fn () => EcommercePromotion::query()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'value' => $data['value'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
        ]));
    }

    public function update(EcommercePromotion $promotion, array $data): EcommercePromotion
    {
        return DB::transaction(function () use ($promotion, $data) {
            $promotion->update([
                'name' => $data['name'],
                'type' => $data['type'],
                'value' => $data['value'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'is_active' => $data['is_active'] ?? $promotion->is_active,
                'description' => $data['description'] ?? $promotion->description,
            ]);

            return $promotion->fresh();
        });
    }

    public function delete(EcommercePromotion $promotion): void
    {
        DB::transaction(fn () => $promotion->delete());
    }
}
