<?php

namespace App\Http\Middleware;

use App\Core\Module\ModuleManager;
use App\Core\Support\ShopComplexity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;
use Modules\Billing\Services\PlanService;
use Modules\Ecommerce\Services\CartService;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'enabledModules' => fn () => app(ModuleManager::class)->enabledCodes(),
            'shopFlags' => fn () => ShopComplexity::flags(),
            'subscription' => function () {
                try {
                    if (! Schema::hasTable('subscriptions')) {
                        return null;
                    }
                    $sub = app(PlanService::class)->activeSubscription();

                    return $sub ? [
                        'plan_name' => $sub->plan?->name,
                        'plan_code' => $sub->plan?->code,
                    ] : null;
                } catch (\Throwable) {
                    return null;
                }
            },
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'receipt' => $request->session()->get('receipt'),
                'order_placed' => $request->session()->get('order_placed'),
            ],
            'cartCount' => fn () => (int) collect($request->session()->get('ecommerce_cart', []))->sum('quantity'),
            'shopCart' => function () {
                try {
                    if (! app(ModuleManager::class)->enabled('ecommerce')) {
                        return null;
                    }

                    return app(CartService::class)->detailed();
                } catch (\Throwable) {
                    return null;
                }
            },
        ];
    }
}
