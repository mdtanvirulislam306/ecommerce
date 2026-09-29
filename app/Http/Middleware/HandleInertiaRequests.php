<?php

namespace App\Http\Middleware;

use App\Core\Module\ModuleManager;
use App\Core\Permission\PermissionRegistry;
use App\Core\Support\ShopComplexity;
use App\Core\Tenant\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;
use Modules\Billing\Services\PlanService;
use Modules\Ecommerce\Services\CartService;
use Modules\Ecommerce\Services\CustomerAccountService;
use Modules\Platform\Services\ImpersonationService;

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
        $user = $request->user('web');

        return [
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
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_platform_admin' => (bool) $user->is_platform_admin,
                    'is_owner' => (bool) $user->is_owner,
                    'tenant_id' => $user->tenant_id,
                ] : null,
                'full_access' => fn () => (bool) $user?->hasFullShopAccess(),
                'permissions' => fn () => $user?->hasFullShopAccess() === false
                    ? $user->permissionKeys()
                    : [],
                'denied_paths' => fn () => $user
                    ? app(PermissionRegistry::class)->deniedPagePaths($user)
                    : [],
            ],
            'impersonation' => fn () => app(ImpersonationService::class)->current($request),
            'shopCustomer' => function () use ($request) {
                $account = $request->user('customer');

                return $account ? app(CustomerAccountService::class)->profile($account) : null;
            },
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
    }
}
