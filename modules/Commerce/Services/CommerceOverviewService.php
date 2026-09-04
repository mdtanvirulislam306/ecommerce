<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Modules\Commerce\Models\Commission;
use Modules\Commerce\Models\Coupon;
use Modules\Commerce\Models\CustomerWallet;
use Modules\Commerce\Models\Referral;
use Modules\Commerce\Models\Shipment;

class CommerceOverviewService extends Service
{
    public function __construct(
        private PromotionService $promotionService,
        private CouponService $couponService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $promotionStats = $this->promotionService->overviewStats();

        return [
            'promotions' => $promotionStats,
            'coupons' => [
                'total' => $this->couponService->count(),
                'active' => Coupon::query()->where('is_active', true)->count(),
            ],
            'referrals' => [
                'total' => Referral::query()->count(),
                'pending' => Referral::query()->where('status', 'pending')->count(),
                'completed' => Referral::query()->where('status', 'completed')->count(),
            ],
            'shipments' => [
                'total' => Shipment::query()->count(),
                'pending' => Shipment::query()->where('status', 'pending')->count(),
                'in_transit' => Shipment::query()->where('status', 'in_transit')->count(),
                'delivered' => Shipment::query()->where('status', 'delivered')->count(),
            ],
            'wallets' => [
                'total' => CustomerWallet::query()->count(),
                'active' => CustomerWallet::query()->where('is_active', true)->count(),
                'total_balance' => (float) CustomerWallet::query()->sum('balance'),
            ],
            'commissions' => [
                'total' => Commission::query()->count(),
                'active' => Commission::query()->where('is_active', true)->count(),
            ],
        ];
    }
}
