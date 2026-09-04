<?php

namespace Modules\Billing\Services;

use App\Core\Module\ModuleManager;
use App\Core\Support\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

            if (! Subscription::query()->where('status', SubscriptionStatus::Active)->exists()) {
                Subscription::query()->create([
                    'plan_id' => $pro->id,
                    'status' => SubscriptionStatus::Active,
                    'starts_at' => now(),
                ]);
            }

            foreach (['multi_price' => false, 'multi_warehouse' => false, 'multi_branch' => false] as $key => $value) {
                if (! ShopSetting::query()->where('key', $key)->exists()) {
                    ShopSetting::setValue($key, $value);
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

    public function activeSubscription(): ?Subscription
    {
        return Subscription::query()
            ->with('plan.modules')
            ->where('status', SubscriptionStatus::Active)
            ->latest('id')
            ->first();
    }

    /**
     * @return list<string>
     */
    public function enabledModuleCodesFromSubscription(): array
    {
        return Cache::remember(self::ENABLED_MODULES_CACHE_KEY, 300, function () {
            $subscription = $this->activeSubscription();

            if ($subscription?->plan) {
                return $subscription->plan->modules->pluck('module_code')->all();
            }

            $free = Plan::query()->where('code', PlanCode::Free->value)->with('modules')->first();

            return $free?->modules->pluck('module_code')->all() ?? $this->freeModuleCodes();
        });
    }

    public function forgetEnabledCache(): void
    {
        Cache::forget(self::ENABLED_MODULES_CACHE_KEY);
    }

    public function assignPlan(Plan $plan): Subscription
    {
        return DB::transaction(function () use ($plan) {
            Subscription::query()
                ->where('status', SubscriptionStatus::Active)
                ->update(['status' => SubscriptionStatus::Cancelled, 'ends_at' => now()]);

            $subscription = Subscription::query()->create([
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
                'starts_at' => now(),
            ]);

            $this->forgetEnabledCache();

            return $subscription;
        });
    }
}
