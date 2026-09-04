<?php

namespace Modules\Marketing\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Marketing\Enums\DiscountType;
use Modules\Marketing\Models\MarketingCoupon;

class MarketingCouponService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return MarketingCoupon::query()
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (MarketingCoupon $coupon) => $this->format($coupon));
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
    public function create(array $data): MarketingCoupon
    {
        return MarketingCoupon::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'] ?? DiscountType::Percentage->value,
            'value' => $data['value'] ?? 0,
            'usage_limit' => $data['usage_limit'] ?? null,
            'used_count' => (int) ($data['used_count'] ?? 0),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MarketingCoupon $coupon, array $data): MarketingCoupon
    {
        $coupon->update([
            'code' => $data['code'] ?? $coupon->code,
            'name' => $data['name'] ?? $coupon->name,
            'type' => $data['type'] ?? $coupon->type,
            'value' => $data['value'] ?? $coupon->value,
            'usage_limit' => $data['usage_limit'] ?? $coupon->usage_limit,
            'used_count' => $data['used_count'] ?? $coupon->used_count,
            'starts_at' => $data['starts_at'] ?? $coupon->starts_at,
            'ends_at' => $data['ends_at'] ?? $coupon->ends_at,
            'is_active' => $data['is_active'] ?? $coupon->is_active,
            'description' => $data['description'] ?? $coupon->description,
        ]);

        return $coupon->fresh();
    }

    public function delete(MarketingCoupon $coupon): void
    {
        $coupon->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(MarketingCoupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'type' => $coupon->type?->value,
            'type_label' => $coupon->type?->label(),
            'value' => $coupon->value,
            'usage_limit' => $coupon->usage_limit,
            'used_count' => $coupon->used_count,
            'starts_at' => $coupon->starts_at?->toIso8601String(),
            'ends_at' => $coupon->ends_at?->toIso8601String(),
            'is_active' => $coupon->is_active,
            'description' => $coupon->description,
            'created_at' => $coupon->created_at?->toIso8601String(),
        ];
    }
}
