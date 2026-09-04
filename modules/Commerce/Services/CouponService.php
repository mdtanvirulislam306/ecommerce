<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Commerce\Models\Coupon;

class CouponService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Coupon::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Coupon
    {
        return Coupon::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'],
            'value' => $data['value'] ?? 0,
            'usage_limit' => $data['usage_limit'] ?? null,
            'used_count' => $data['used_count'] ?? 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Coupon $coupon, array $data): Coupon
    {
        $coupon->update([
            'code' => $data['code'] ?? $coupon->code,
            'name' => $data['name'] ?? $coupon->name,
            'type' => $data['type'] ?? $coupon->type,
            'value' => $data['value'] ?? $coupon->value,
            'usage_limit' => array_key_exists('usage_limit', $data) ? $data['usage_limit'] : $coupon->usage_limit,
            'used_count' => $data['used_count'] ?? $coupon->used_count,
            'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $coupon->starts_at,
            'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $coupon->ends_at,
            'is_active' => $data['is_active'] ?? $coupon->is_active,
            'description' => array_key_exists('description', $data) ? $data['description'] : $coupon->description,
        ]);

        return $coupon->fresh();
    }

    public function delete(Coupon $coupon): void
    {
        $coupon->delete();
    }

    public function count(): int
    {
        return Coupon::query()->count();
    }
}
