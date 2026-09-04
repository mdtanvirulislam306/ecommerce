<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\EcommerceCoupon;

class EcommerceCouponService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return EcommerceCoupon::query()
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): EcommerceCoupon
    {
        return DB::transaction(fn () => EcommerceCoupon::query()->create([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'type' => $data['type'],
            'value' => $data['value'],
            'usage_limit' => $data['usage_limit'] ?? null,
            'used_count' => 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
        ]));
    }

    public function update(EcommerceCoupon $coupon, array $data): EcommerceCoupon
    {
        return DB::transaction(function () use ($coupon, $data) {
            $coupon->update([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'type' => $data['type'],
                'value' => $data['value'],
                'usage_limit' => $data['usage_limit'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'is_active' => $data['is_active'] ?? $coupon->is_active,
                'description' => $data['description'] ?? $coupon->description,
            ]);

            return $coupon->fresh();
        });
    }

    public function delete(EcommerceCoupon $coupon): void
    {
        DB::transaction(fn () => $coupon->delete());
    }
}
