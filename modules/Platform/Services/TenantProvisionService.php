<?php

namespace Modules\Platform\Services;

use App\Core\Module\ModuleManager;
use App\Core\Support\Service;
use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantModuleOverride;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Billing\Enums\SubscriptionStatus;
use Modules\Billing\Models\Plan;
use Modules\Billing\Models\ShopSetting;
use Modules\Billing\Models\Subscription;
use Modules\Billing\Services\PlanService;
use Modules\Commerce\Models\PriceList;
use Modules\Inventory\Models\Warehouse;
use Modules\Settings\Services\AuditLogService;

class TenantProvisionService extends Service
{
    public function __construct(
        private readonly PlanService $plans,
        private readonly ModuleManager $modules,
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listTenants(): array
    {
        return Tenant::query()
            ->with(['primaryDomain', 'domains'])
            ->orderByDesc('id')
            ->get()
            ->map(function (Tenant $tenant) {
                $subscription = Subscription::query()
                    ->withoutGlobalScopes()
                    ->with('plan')
                    ->where('tenant_id', $tenant->id)
                    ->where('status', SubscriptionStatus::Active)
                    ->latest('id')
                    ->first();

                $owner = $this->ownerOf($tenant);

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'status' => $tenant->status,
                    'notes' => $tenant->notes,
                    'domain' => $tenant->primaryDomain?->domain ?? $tenant->domains->first()?->domain,
                    'plan_name' => $subscription?->plan?->name,
                    'plan_code' => $subscription?->plan?->code,
                    'owner_name' => $owner?->name,
                    'owner_email' => $owner?->email,
                    'created_at' => $tenant->created_at?->toIso8601String(),
                ];
            })
            ->all();
    }

    /**
     * @param  array{
     *     name: string,
     *     slug?: string|null,
     *     domain: string,
     *     owner_name: string,
     *     owner_email: string,
     *     owner_password: string,
     *     plan_id: int,
     *     module_overrides?: array<string, bool>,
     *     payment_note?: string|null,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     notes?: string|null,
     *     status?: string
     * }  $data
     */
    public function provision(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $slug = $data['slug'] ?? Str::slug($data['name']);
            $slug = $this->uniqueSlug($slug);
            $domain = strtolower(trim($data['domain']));

            if (TenantDomain::query()->where('domain', $domain)->exists()) {
                throw ValidationException::withMessages(['domain' => 'Domain is already in use.']);
            }

            $plan = Plan::query()->findOrFail($data['plan_id']);

            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'slug' => $slug,
                'status' => $data['status'] ?? Tenant::STATUS_ACTIVE,
                'notes' => $data['notes'] ?? null,
            ]);

            TenantDomain::query()->create([
                'tenant_id' => $tenant->id,
                'domain' => $domain,
                'is_primary' => true,
                'is_active' => true,
            ]);

            User::query()->create([
                'tenant_id' => $tenant->id,
                'is_platform_admin' => false,
                'is_owner' => true,
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => Hash::make($data['owner_password']),
                'email_verified_at' => now(),
            ]);

            Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
                'starts_at' => ! empty($data['starts_at']) ? $data['starts_at'] : now(),
                'ends_at' => $data['ends_at'] ?? null,
                'payment_note' => $data['payment_note'] ?? null,
            ]);

            foreach ($data['module_overrides'] ?? [] as $code => $enabled) {
                if (! $enabled) {
                    continue;
                }
                TenantModuleOverride::query()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'module_code' => $code],
                    ['enabled' => true],
                );
            }

            foreach (['multi_price' => false, 'multi_warehouse' => false, 'multi_branch' => false] as $key => $value) {
                ShopSetting::setValue($key, $value, $tenant->id);
            }

            $this->seedProgressiveDefaults($tenant->id);

            $this->plans->forgetEnabledCache($tenant->id);

            try {
                app(AuditLogService::class)->create([
                    'user_id' => auth()->id(),
                    'action' => 'tenant.provisioned',
                    'subject_type' => Tenant::class,
                    'subject_id' => $tenant->id,
                    'properties' => [
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'domain' => $domain,
                        'plan_id' => $plan->id,
                    ],
                    'ip_address' => request()->ip(),
                ]);
            } catch (\Throwable) {
                // Audit is best-effort; provisioning must not fail if Settings audit is unavailable.
            }

            return $tenant->fresh(['domains']);
        });
    }

    /**
     * @param  array{
     *     name?: string,
     *     status?: string,
     *     notes?: string|null,
     *     plan_id?: int,
     *     payment_note?: string|null,
     *     ends_at?: string|null,
     *     module_overrides?: array<string, bool>,
     *     domain?: string
     * }  $data
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            $tenant->update([
                'name' => $data['name'] ?? $tenant->name,
                'status' => $data['status'] ?? $tenant->status,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $tenant->notes,
            ]);

            if (! empty($data['domain'])) {
                $domain = strtolower(trim($data['domain']));
                $exists = TenantDomain::query()
                    ->where('domain', $domain)
                    ->where('tenant_id', '!=', $tenant->id)
                    ->exists();
                if ($exists) {
                    throw ValidationException::withMessages(['domain' => 'Domain is already in use.']);
                }

                $primary = $tenant->primaryDomain ?? $tenant->domains()->first();
                if ($primary) {
                    $primary->update(['domain' => $domain, 'is_primary' => true, 'is_active' => true]);
                } else {
                    TenantDomain::query()->create([
                        'tenant_id' => $tenant->id,
                        'domain' => $domain,
                        'is_primary' => true,
                        'is_active' => true,
                    ]);
                }
            }

            if (! empty($data['plan_id'])) {
                $this->plans->assignPlan(
                    Plan::query()->findOrFail($data['plan_id']),
                    $tenant->id,
                    $data['payment_note'] ?? null,
                );
                if (array_key_exists('ends_at', $data)) {
                    Subscription::query()
                        ->withoutGlobalScopes()
                        ->where('tenant_id', $tenant->id)
                        ->where('status', SubscriptionStatus::Active)
                        ->update(['ends_at' => $data['ends_at']]);
                }
            }

            if (array_key_exists('module_overrides', $data)) {
                TenantModuleOverride::query()->where('tenant_id', $tenant->id)->delete();
                foreach ($data['module_overrides'] ?? [] as $code => $enabled) {
                    TenantModuleOverride::query()->create([
                        'tenant_id' => $tenant->id,
                        'module_code' => $code,
                        'enabled' => (bool) $enabled,
                    ]);
                }
                $this->plans->forgetEnabledCache($tenant->id);
            }

            if (array_key_exists('owner_password', $data) && filled($data['owner_password'])) {
                $this->resetOwnerPassword($tenant, (string) $data['owner_password']);
            }

            return $tenant->fresh(['domains']);
        });
    }

    public function suspend(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => Tenant::STATUS_SUSPENDED]);

        return $tenant->fresh();
    }

    public function activate(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => Tenant::STATUS_ACTIVE]);

        return $tenant->fresh();
    }

    public function resetOwnerPassword(Tenant $tenant, string $password): void
    {
        $owner = $this->ownerOf($tenant);
        if ($owner === null) {
            throw ValidationException::withMessages(['owner' => 'Owner user not found.']);
        }
        $owner->update(['password' => Hash::make($password)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'code', 'price_monthly', 'currency', 'is_default']),
            'modules' => collect($this->modules->all())->map(fn ($m) => [
                'code' => $m->code,
                'name' => $m->name,
                'is_core' => $m->isCore,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatDetail(Tenant $tenant): array
    {
        $subscription = Subscription::query()
            ->withoutGlobalScopes()
            ->with('plan.modules')
            ->where('tenant_id', $tenant->id)
            ->where('status', SubscriptionStatus::Active)
            ->latest('id')
            ->first();

        $owner = $this->ownerOf($tenant);
        $overrides = TenantModuleOverride::query()->where('tenant_id', $tenant->id)->get()
            ->mapWithKeys(fn ($row) => [$row->module_code => $row->enabled])
            ->all();

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'status' => $tenant->status,
            'notes' => $tenant->notes,
            'domain' => $tenant->primaryDomain?->domain ?? $tenant->domains->first()?->domain,
            'plan_id' => $subscription?->plan_id,
            'payment_note' => $subscription?->payment_note,
            'starts_at' => $subscription?->starts_at?->toDateString(),
            'ends_at' => $subscription?->ends_at?->toDateString(),
            'module_overrides' => $overrides,
            'owner' => $owner ? [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
            ] : null,
            ...$this->formOptions(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, email: string, is_owner: bool, status: string, status_label: string, roles: list<string>, last_login_at: string|null, can_impersonate: bool}>
     */
    public function team(Tenant $tenant): array
    {
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_platform_admin', false)
            ->with(['roles' => fn ($query) => $query->withoutGlobalScopes()->select('roles.id', 'roles.name')])
            ->orderByDesc('is_owner')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_owner' => (bool) $user->is_owner,
                'status' => $user->staffStatus()->value,
                'status_label' => $user->staffStatus()->label(),
                'roles' => $user->roles->pluck('name')->all(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'can_impersonate' => ! $user->isDeactivated() && ! $user->hasPendingInvitation(),
            ])
            ->all();
    }

    /**
     * Shops provisioned before the owner flag existed fall back to their first user.
     */
    private function ownerOf(Tenant $tenant): ?User
    {
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('is_owner')
            ->orderBy('id')
            ->first();
    }

    private function uniqueSlug(string $slug): string
    {
        $base = Str::slug($slug) ?: 'shop';
        $candidate = $base;
        $i = 1;
        while (Tenant::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    /**
     * Provisioning runs on a platform request, where the tenant scope is disabled, so lookups
     * must name the new shop explicitly or they match another shop's defaults.
     */
    private function seedProgressiveDefaults(int $tenantId): void
    {
        $previous = $this->tenantContext->get();
        $this->tenantContext->set(Tenant::query()->find($tenantId));

        try {
            if (Schema::hasTable('price_lists')) {
                PriceList::query()->where('tenant_id', $tenantId)->firstOrCreate(
                    ['code' => 'retail'],
                    [
                        'name' => 'Retail',
                        'description' => 'Standard retail pricing',
                        'currency' => 'BDT',
                        'is_active' => true,
                        'is_default' => true,
                        'sort_order' => 1,
                    ],
                );
            }

            if (Schema::hasTable('warehouses')) {
                Warehouse::query()->where('tenant_id', $tenantId)->firstOrCreate(
                    ['code' => 'MAIN'],
                    [
                        'name' => 'Main Warehouse',
                        'address' => 'Primary stock location',
                        'is_active' => true,
                        'is_default' => true,
                        'sort_order' => 1,
                    ],
                );
            }
        } finally {
            $this->tenantContext->set($previous);
        }
    }
}
