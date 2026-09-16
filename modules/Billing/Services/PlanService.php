<?php

namespace Modules\Billing\Services;

use App\Core\Module\ModuleManager;
use App\Core\Support\Service;
use App\Core\Tenant\TenantContext;
use App\Models\TenantModuleOverride;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Billing\Enums\PlanCode;
use Modules\Billing\Enums\SubscriptionStatus;
use Modules\Billing\Models\Plan;
use Modules\Billing\Models\PlanModule;
use Modules\Billing\Models\ShopSetting;
use Modules\Billing\Models\Subscription;

class PlanService extends Service
{
    public const ENABLED_MODULES_CACHE_KEY = 'billing.enabled_module_codes';

    /**
     * @return list<string>
     */
    public function freeModuleCodes(): array
    {
        return ['catalog', 'commerce', 'inventory', 'sales', 'ecommerce'];
    }

    /**
     * @return list<string>
     */
    public function allDiscoverableCodes(ModuleManager $modules): array
    {
        return array_keys($modules->all());
    }

    public function ensureDefaults(ModuleManager $modules): void
    {
        DB::transaction(function () use ($modules) {
            $free = Plan::query()->firstOrCreate(
                ['code' => PlanCode::Free->value],
                [
                    'name' => 'Free',
                    'description' => 'Simple shop: catalog, pricing, stock, sales, storefront',
                    'price_monthly' => 0,
                    'currency' => 'BDT',
                    'is_active' => true,
                    'is_default' => true,
                    'sort_order' => 1,
                ],
            );

            $pro = Plan::query()->firstOrCreate(
                ['code' => PlanCode::Pro->value],
                [
                    'name' => 'Pro',
                    'description' => 'Full Commerce OS: purchase, CRM, accounting, POS, and more',
                    'price_monthly' => 499900,
                    'currency' => 'BDT',
                    'is_active' => true,
                    'is_default' => false,
                    'sort_order' => 2,
                ],
            );

            $this->syncPlanModules($free, $this->freeModuleCodes());
            $this->syncPlanModules($pro, $this->allDiscoverableCodes($modules));

            $tenantId = app(TenantContext::class)->id()
                ?? (Schema::hasTable('tenants') ? DB::table('tenants')->where('slug', 'default')->value('id') : null);

            if ($tenantId && ! Subscription::query()->where('tenant_id', $tenantId)->where('status', SubscriptionStatus::Active)->exists()) {
                Subscription::query()->create([
                    'tenant_id' => $tenantId,
                    'plan_id' => $pro->id,
                    'status' => SubscriptionStatus::Active,
                    'starts_at' => now(),
                ]);
            }

            foreach (['multi_price' => false, 'multi_warehouse' => false, 'multi_branch' => false] as $key => $value) {
                if (ShopSetting::query()->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->where('key', $key)->doesntExist()) {
                    ShopSetting::setValue($key, $value, $tenantId ? (int) $tenantId : null);
                }
            }
        });

        $this->forgetEnabledCache();
    }

    /**
     * @param  list<string>  $codes
     */
    public function syncPlanModules(Plan $plan, array $codes): void
    {
        $codes = array_values(array_unique($codes));
        PlanModule::query()->where('plan_id', $plan->id)->whereNotIn('module_code', $codes)->delete();

        foreach ($codes as $code) {
            PlanModule::query()->firstOrCreate([
                'plan_id' => $plan->id,
                'module_code' => $code,
            ]);
        }

        $this->forgetEnabledCache();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(ModuleManager $modules): array
    {
        return Plan::query()
            ->with('modules')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'currency' => $plan->currency,
                'is_active' => $plan->is_active,
                'is_default' => $plan->is_default,
                'module_codes' => $plan->modules->pluck('module_code')->values()->all(),
                'available_modules' => collect($modules->all())->map(fn ($m) => [
                    'code' => $m->code,
                    'name' => $m->name,
                    'is_core' => $m->isCore,
                ])->values()->all(),
            ])
            ->all();
    }

    /**
     * @param  array{name: string, description?: string|null, price_monthly?: int, is_active?: bool, module_codes?: list<string>}  $data
     */
    public function update(Plan $plan, array $data): Plan
    {
        $plan->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $plan->description,
            'price_monthly' => $data['price_monthly'] ?? $plan->price_monthly,
            'is_active' => $data['is_active'] ?? $plan->is_active,
        ]);

        if (array_key_exists('module_codes', $data)) {
            $this->syncPlanModules($plan, $data['module_codes'] ?? []);
        }

        return $plan->fresh('modules');
    }

    public function activeSubscription(?int $tenantId = null): ?Subscription
    {
        $tenantId ??= app(TenantContext::class)->id();

        $query = Subscription::query()
            ->with('plan.modules')
            ->where('status', SubscriptionStatus::Active);

        if ($tenantId !== null && Schema::hasColumn('subscriptions', 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        $subscription = $query->latest('id')->first();

        if ($subscription && $subscription->ends_at && $subscription->ends_at->isPast()) {
            return null;
        }

        return $subscription;
    }

    /**
     * @return list<string>
     */
    public function enabledModuleCodesFromSubscription(?int $tenantId = null): array
    {
        $tenantId ??= app(TenantContext::class)->id();
        $cacheKey = self::ENABLED_MODULES_CACHE_KEY.($tenantId ? '.'.$tenantId : '');

        return Cache::remember($cacheKey, 300, function () use ($tenantId) {
            $codes = [];

            $subscription = $this->activeSubscription($tenantId);

            if ($subscription?->plan) {
                $codes = $subscription->plan->modules->pluck('module_code')->all();
            } else {
                $free = Plan::query()->where('code', PlanCode::Free->value)->with('modules')->first();
                $codes = $free?->modules->pluck('module_code')->all() ?? $this->freeModuleCodes();
            }

            if ($tenantId && Schema::hasTable('tenant_module_overrides')) {
                $overrides = TenantModuleOverride::query()
                    ->where('tenant_id', $tenantId)
                    ->get();

                foreach ($overrides as $override) {
                    if ($override->enabled) {
                        $codes[] = $override->module_code;
                    } else {
                        $codes = array_values(array_filter($codes, fn ($c) => $c !== $override->module_code));
                    }
                }
            }

            return array_values(array_unique($codes));
        });
    }

    public function forgetEnabledCache(?int $tenantId = null): void
    {
        $tenantId ??= app(TenantContext::class)->id();
        Cache::forget(self::ENABLED_MODULES_CACHE_KEY);
        if ($tenantId) {
            Cache::forget(self::ENABLED_MODULES_CACHE_KEY.'.'.$tenantId);
        }
    }

    public function assignPlan(Plan $plan, ?int $tenantId = null, ?string $paymentNote = null): Subscription
    {
        $tenantId ??= app(TenantContext::class)->id();

        return DB::transaction(function () use ($plan, $tenantId, $paymentNote) {
            $query = Subscription::query()->where('status', SubscriptionStatus::Active);
            if ($tenantId !== null && Schema::hasColumn('subscriptions', 'tenant_id')) {
                $query->where('tenant_id', $tenantId);
            }
            $query->update(['status' => SubscriptionStatus::Cancelled, 'ends_at' => now()]);

            $subscription = Subscription::query()->create([
                'tenant_id' => $tenantId,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
                'starts_at' => now(),
                'payment_note' => $paymentNote,
            ]);

            $this->forgetEnabledCache($tenantId);

            return $subscription;
        });
    }
}
