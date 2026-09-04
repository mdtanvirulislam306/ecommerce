<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Modules\Ecommerce\Models\CmsMenu;
use Modules\Ecommerce\Models\CmsPage;
use Modules\Ecommerce\Models\EcommerceCoupon;
use Modules\Ecommerce\Models\EcommercePromotion;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Models\StoreDomain;
use Modules\Ecommerce\Models\ThemeSetting;

class StoreDashboardService extends Service
{
    public function stats(): array
    {
        $stats = [
            'domains' => StoreDomain::query()->count(),
            'active_domains' => StoreDomain::query()->where('is_active', true)->count(),
            'pages' => CmsPage::query()->count(),
            'published_pages' => CmsPage::query()->where('is_published', true)->count(),
            'published_themes' => ThemeSetting::query()->where('is_published', true)->count(),
            'installed_themes' => ThemeSetting::query()->where('is_installed', true)->count(),
            'menus' => CmsMenu::query()->count(),
            'coupons' => EcommerceCoupon::query()->count(),
            'promotions' => EcommercePromotion::query()->count(),
        ];

        if (class_exists(OnlineOrder::class)) {
            $stats['orders'] = OnlineOrder::query()->count();
            $stats['pending_orders'] = OnlineOrder::query()->where('status', 'pending')->count();
        } else {
            $stats['orders'] = 0;
            $stats['pending_orders'] = 0;
        }

        return $stats;
    }
}
