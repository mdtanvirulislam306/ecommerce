<?php

namespace Modules\Marketing\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Marketing\Enums\DiscountType;
use Modules\Marketing\Models\MarketingPromotion;

class MarketingPromotionService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return MarketingPromotion::query()
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (MarketingPromotion $promotion) => $this->format($promotion));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function typeOptions(): array
    {
        return collect(DiscountType::cases())->map(fn (DiscountType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MarketingPromotion
    {
        return MarketingPromotion::query()->create([
            'name' => $data['name'],
            'type' => $data['type'] ?? DiscountType::Percentage->value,
            'value' => $data['value'] ?? 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MarketingPromotion $promotion, array $data): MarketingPromotion
    {
        $promotion->update([
            'name' => $data['name'] ?? $promotion->name,
            'type' => $data['type'] ?? $promotion->type,
            'value' => $data['value'] ?? $promotion->value,
            'starts_at' => $data['starts_at'] ?? $promotion->starts_at,
            'ends_at' => $data['ends_at'] ?? $promotion->ends_at,
            'is_active' => $data['is_active'] ?? $promotion->is_active,
            'description' => $data['description'] ?? $promotion->description,
        ]);

        return $promotion->fresh();
    }

    public function delete(MarketingPromotion $promotion): void
    {
        $promotion->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(MarketingPromotion $promotion): array
    {
        return [
            'id' => $promotion->id,
            'name' => $promotion->name,
            'type' => $promotion->type?->value,
            'type_label' => $promotion->type?->label(),
            'value' => $promotion->value,
            'starts_at' => $promotion->starts_at?->toIso8601String(),
            'ends_at' => $promotion->ends_at?->toIso8601String(),
            'is_active' => $promotion->is_active,
            'description' => $promotion->description,
            'created_at' => $promotion->created_at?->toIso8601String(),
        ];
    }
}
