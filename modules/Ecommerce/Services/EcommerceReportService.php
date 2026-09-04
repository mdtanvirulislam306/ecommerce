<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Modules\Ecommerce\Models\CmsPage;
use Modules\Ecommerce\Models\EcommerceCoupon;
use Modules\Ecommerce\Models\EcommercePromotion;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Models\StoreDomain;
use Modules\Ecommerce\Models\ThemeSetting;

class EcommerceReportService extends Service
{
    public function overview(): array
    {
        return [
            'domains' => StoreDomain::query()->count(),
            'pages' => CmsPage::query()->count(),
            'published_pages' => CmsPage::query()->where('is_published', true)->count(),
            'coupons' => EcommerceCoupon::query()->count(),
            'active_coupons' => EcommerceCoupon::query()->where('is_active', true)->count(),
            'promotions' => EcommercePromotion::query()->count(),
            'active_promotions' => EcommercePromotion::query()->where('is_active', true)->count(),
            'published_themes' => ThemeSetting::query()->where('is_published', true)->count(),
            'orders' => OnlineOrder::query()->count(),
            'pending_orders' => OnlineOrder::query()->where('status', 'pending')->count(),
            'confirmed_orders' => OnlineOrder::query()->where('status', 'confirmed')->count(),
            'revenue' => number_format(
                (float) OnlineOrder::query()->where('status', 'confirmed')->sum('grand_total'),
                2,
                '.',
                '',
            ),
        ];
    }
}
