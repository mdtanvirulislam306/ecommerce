<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Enums\DiscountType;
use Modules\Ecommerce\Models\EcommerceCoupon;

class StorefrontCouponService extends Service
{
    /**
     * @return array{coupon: EcommerceCoupon, discount: float}
     *
     * @throws ValidationException when the code cannot be used right now
     */
    public function resolve(string $code, float $subtotal, bool $lockForRedeem = false): array
    {
        $coupon = EcommerceCoupon::query()
            ->where('code', strtoupper(trim($code)))
            ->when($lockForRedeem, fn ($query) => $query->lockForUpdate())
            ->first();

        $problem = match (true) {
            $coupon === null || ! $coupon->is_active => 'This coupon code is not valid.',
            $coupon->starts_at !== null && $coupon->starts_at->isFuture() => 'This coupon is not active yet.',
            $coupon->ends_at !== null && $coupon->ends_at->isPast() => 'This coupon has expired.',
            $coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit => 'This coupon has reached its usage limit.',
            default => null,
        };

        if ($problem !== null) {
            throw ValidationException::withMessages(['coupon' => $problem]);
        }

        return ['coupon' => $coupon, 'discount' => $this->discountFor($coupon, $subtotal)];
    }

    public function redeem(EcommerceCoupon $coupon): void
    {
        $coupon->increment('used_count');
    }

    private function discountFor(EcommerceCoupon $coupon, float $subtotal): float
    {
        $value = (float) $coupon->value;

        $discount = DiscountType::tryFrom((string) $coupon->type) === DiscountType::Percentage
            ? $subtotal * min($value, 100) / 100
            : $value;

        return round(min(max($discount, 0), $subtotal), 2);
    }
}
