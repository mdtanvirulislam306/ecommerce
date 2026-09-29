<?php

namespace Modules\Platform\Services;

use App\Core\Support\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Enums\SubscriptionStatus;
use Modules\Billing\Models\Plan;
use Modules\Billing\Models\Subscription;

class PlatformDashboardService extends Service
{
    public const EXPIRING_WITHIN_DAYS = 14;

    public const ACTIVE_USER_WINDOW_DAYS = 30;

    public const USAGE_ROWS = 8;

    /**
     * @return array{
     *     shops: array{total: int, active: int, trial: int, suspended: int, new_this_month: int},
     *     revenue: array{mrr: int, paying_shops: int, currency: string},
     *     users: array{total: int, active_recently: int, pending_invitations: int, window_days: int},
     *     plan_mix: list<array{id: int, name: string, code: string, price_monthly: int, is_active: bool, shops: int, mrr: int}>,
     *     attention: list<array{id: int, name: string, reason: string, label: string, date: string|null}>,
     *     recent_shops: list<array{id: int, name: string, domain: string|null, status: string, plan_name: string|null, created_at: string|null}>,
     *     usage: list<array{id: int, name: string, users: int, products: int, orders: int, orders_recent: int}>
     * }
     */
    public function overview(): array
    {
        $tenants = Tenant::query()->with(['primaryDomain', 'domains'])->orderByDesc('id')->get();
        $subscriptions = $this->currentSubscriptions();
        $billable = $tenants->filter(fn (Tenant $tenant) => ! $tenant->isSuspended() && $this->isCurrent($subscriptions->get($tenant->id)));

        return [
            'shops' => [
                'total' => $tenants->count(),
                'active' => $tenants->where('status', Tenant::STATUS_ACTIVE)->count(),
                'trial' => $tenants->where('status', Tenant::STATUS_TRIAL)->count(),
                'suspended' => $tenants->where('status', Tenant::STATUS_SUSPENDED)->count(),
                'new_this_month' => $tenants->filter(fn (Tenant $tenant) => $tenant->created_at?->gte(now()->startOfMonth()))->count(),
            ],
            'revenue' => [
                'mrr' => $billable->sum(fn (Tenant $tenant) => (int) $subscriptions->get($tenant->id)?->plan?->price_monthly),
                'paying_shops' => $billable->filter(fn (Tenant $tenant) => (int) $subscriptions->get($tenant->id)?->plan?->price_monthly > 0)->count(),
                'currency' => 'BDT',
            ],
            'users' => $this->userStats(),
            'plan_mix' => $this->planMix($billable, $subscriptions),
            'attention' => $this->attention($tenants, $subscriptions),
            'recent_shops' => $tenants->take(5)->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->primaryDomain?->domain ?? $tenant->domains->first()?->domain,
                'status' => $tenant->status,
                'plan_name' => $subscriptions->get($tenant->id)?->plan?->name,
                'created_at' => $tenant->created_at?->toIso8601String(),
            ])->values()->all(),
            'usage' => $this->usage($tenants),
        ];
    }

    /**
     * The newest active subscription of every shop, keyed by tenant id.
     *
     * @return Collection<int, Subscription>
     */
    private function currentSubscriptions(): Collection
    {
        return Subscription::query()
            ->withoutGlobalScopes()
            ->with('plan')
            ->where('status', SubscriptionStatus::Active)
            ->orderBy('id')
            ->get()
            ->keyBy('tenant_id');
    }

    private function isCurrent(?Subscription $subscription): bool
    {
        return $subscription !== null && ($subscription->ends_at === null || $subscription->ends_at->isFuture());
    }

    /**
     * @return array{total: int, active_recently: int, pending_invitations: int, window_days: int}
     */
    private function userStats(): array
    {
        $staff = User::query()->where('is_platform_admin', false);

        return [
            'total' => (clone $staff)->whereNull('deactivated_at')->count(),
            'active_recently' => (clone $staff)->where('last_login_at', '>=', now()->subDays(self::ACTIVE_USER_WINDOW_DAYS))->count(),
            'pending_invitations' => (clone $staff)->whereNotNull('invited_at')->whereNull('invitation_accepted_at')->whereNull('deactivated_at')->count(),
            'window_days' => self::ACTIVE_USER_WINDOW_DAYS,
        ];
    }

    /**
     * @param  Collection<int, Tenant>  $billable
     * @param  Collection<int, Subscription>  $subscriptions
     * @return list<array{id: int, name: string, code: string, price_monthly: int, is_active: bool, shops: int, mrr: int}>
     */
    private function planMix(Collection $billable, Collection $subscriptions): array
    {
        $shopsPerPlan = $billable->countBy(fn (Tenant $tenant) => $subscriptions->get($tenant->id)->plan_id);

        return Plan::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'price_monthly' => $plan->price_monthly,
                'is_active' => $plan->is_active,
                'shops' => (int) ($shopsPerPlan[$plan->id] ?? 0),
                'mrr' => (int) ($shopsPerPlan[$plan->id] ?? 0) * $plan->price_monthly,
            ])
            ->all();
    }

    /**
     * Shops the platform owner should look at: lapsed or lapsing subscriptions, suspensions, and shops without a plan.
     *
     * @param  Collection<int, Tenant>  $tenants
     * @param  Collection<int, Subscription>  $subscriptions
     * @return list<array{id: int, name: string, reason: string, label: string, date: string|null}>
     */
    private function attention(Collection $tenants, Collection $subscriptions): array
    {
        $soon = now()->addDays(self::EXPIRING_WITHIN_DAYS);

        return $tenants
            ->map(function (Tenant $tenant) use ($subscriptions, $soon) {
                $subscription = $subscriptions->get($tenant->id);
                $endsAt = $subscription?->ends_at;

                [$reason, $label, $date] = match (true) {
                    $tenant->isSuspended() => ['suspended', 'Suspended', $tenant->updated_at],
                    $subscription === null => ['no_plan', 'No active plan', null],
                    $endsAt !== null && $endsAt->isPast() => ['expired', 'Plan expired', $endsAt],
                    $endsAt !== null && $endsAt->lte($soon) => ['expiring', 'Plan ends soon', $endsAt],
                    default => [null, null, null],
                };

                return $reason === null ? null : [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'reason' => $reason,
                    'label' => $label,
                    'date' => $date?->toIso8601String(),
                ];
            })
            ->filter()
            ->sortBy(fn (array $row) => ['expired' => 0, 'expiring' => 1, 'no_plan' => 2, 'suspended' => 3][$row['reason']])
            ->values()
            ->all();
    }

    /**
     * Busiest shops by recent orders. Raw counts skip the tenant scope, which is off on platform requests anyway.
     *
     * @param  Collection<int, Tenant>  $tenants
     * @return list<array{id: int, name: string, users: int, products: int, orders: int, orders_recent: int}>
     */
    private function usage(Collection $tenants): array
    {
        $since = now()->subDays(self::ACTIVE_USER_WINDOW_DAYS);
        $users = $this->countsPerTenant(DB::table('users')->where('is_platform_admin', false)->whereNull('deactivated_at'));
        $products = $this->countsPerTenant(DB::table('products'));
        $orders = $this->countsPerTenant(DB::table('sales_orders'));
        $recentOrders = $this->countsPerTenant(DB::table('sales_orders')->where('created_at', '>=', $since));

        return $tenants
            ->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'users' => (int) ($users[$tenant->id] ?? 0),
                'products' => (int) ($products[$tenant->id] ?? 0),
                'orders' => (int) ($orders[$tenant->id] ?? 0),
                'orders_recent' => (int) ($recentOrders[$tenant->id] ?? 0),
            ])
            ->sortByDesc(fn (array $row) => [$row['orders_recent'], $row['orders'], $row['products']])
            ->take(self::USAGE_ROWS)
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function countsPerTenant(Builder $query): array
    {
        return $query
            ->selectRaw('tenant_id, count(*) as aggregate')
            ->whereNotNull('tenant_id')
            ->groupBy('tenant_id')
            ->pluck('aggregate', 'tenant_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
