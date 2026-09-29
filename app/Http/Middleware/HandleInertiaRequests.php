<?php

namespace App\Http\Middleware;

use App\Core\Module\ModuleManager;
use App\Core\Services\SetupChecklistService;
use App\Core\Support\ShopComplexity;
use App\Core\Tenant\TenantContext;
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
        $shared = [
            ...parent::share($request),
            'cartCount' => fn () => app(CartService::class)->count(),
            'tenant' => function () {
                $tenant = app(TenantContext::class)->get();

                return $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'status' => $tenant->status,
                ] : null;
            },
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'is_platform_admin' => (bool) $request->user()->is_platform_admin,
                    'tenant_id' => $request->user()->tenant_id,
                ] : null,
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
                        'ends_at' => $sub->ends_at?->toDateString(),
                        'payment_note' => $sub->payment_note,
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

        if ($this->sharesSetupChecklist($request)) {
            $shared['setupChecklist'] = fn (): array => app(SetupChecklistService::class)->forCurrentTenant();
        }

        return $shared;
    }

    /**
     * Owner and shop-admin pages only. Sales Manager stays on its own dashboard payload.
     */
    private function sharesSetupChecklist(Request $request): bool
    {
        $user = $request->user();

        if ($user === null || ! $request->is('admin', 'admin/*')) {
            return false;
        }

        if ($user->isSalesManager()) {
            return false;
        }

        return app(TenantContext::class)->check();
    }
}
