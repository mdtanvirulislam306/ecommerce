<?php

namespace Modules\Crm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Crm\Models\CrmActivity;
use Modules\Crm\Models\Customer;
use Modules\Crm\Models\Lead;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Pos\Enums\PosOrderStatus;
use Modules\Pos\Models\PosOrder;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Models\SalesOrder;

class CustomerService extends Service
{
    /**
     * @param  array{
     *     search?: string|null,
     *     is_active?: string|null,
     *     customer_group_id?: int|null,
     *     sort?: string|null,
     *     direction?: string|null,
     *     per_page?: int
     * }  $filters
     */
    public function listPaginated(array $filters = []): LengthAwarePaginator
    {
        $perPage = in_array((int) ($filters['per_page'] ?? 25), [10, 25, 50, 100], true)
            ? (int) $filters['per_page']
            : 25;
        $sort = in_array($filters['sort'] ?? '', ['name', 'code', 'company', 'created_at'], true)
            ? $filters['sort']
            : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return Customer::query()
            ->withCount('activities')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            }))
            ->when(
                in_array($filters['is_active'] ?? '', ['0', '1'], true),
                fn ($query) => $query->where('is_active', $filters['is_active'] === '1'),
            )
            ->when($filters['customer_group_id'] ?? null, fn ($query, $groupId) => $query->where('customer_group_id', $groupId))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Customer $customer) => $this->format($customer));
    }

    /**
     * @param  array{
     *     name: string,
     *     code?: string|null,
     *     email?: string|null,
     *     phone?: string|null,
     *     company?: string|null,
     *     address?: string|null,
     *     customer_group_id?: int|null,
     *     is_active?: bool,
     *     notes?: string|null
     * }  $data
     */
    public function create(array $data, ?int $userId = null): Customer
    {
        return Customer::query()->create([
            'code' => $data['code'] ?? $this->nextCode(),
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'address' => $data['address'] ?? null,
            'customer_group_id' => $data['customer_group_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update([
            'code' => $data['code'] ?? $customer->code,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'address' => $data['address'] ?? null,
            'customer_group_id' => $data['customer_group_id'] ?? null,
            'is_active' => $data['is_active'] ?? $customer->is_active,
            'notes' => $data['notes'] ?? null,
        ]);

        return $customer->fresh();
    }

    public function delete(Customer $customer): void
    {
        if ($customer->activities()->exists()) {
            throw ValidationException::withMessages([
                'customer' => 'Cannot delete a customer with activities. Archive (deactivate) instead.',
            ]);
        }

        if (DB::table('leads')->where('converted_customer_id', $customer->id)->exists()) {
            throw ValidationException::withMessages([
                'customer' => 'Cannot delete a customer converted from a lead. Deactivate instead.',
            ]);
        }

        if (
            DB::table('sales_orders')->where('customer_id', $customer->id)->exists()
            || DB::table('sales_quotations')->where('customer_id', $customer->id)->exists()
            || DB::table('pos_orders')->where('customer_id', $customer->id)->exists()
            || DB::table('online_orders')->where('customer_id', $customer->id)->exists()
        ) {
            throw ValidationException::withMessages([
                'customer' => 'Cannot delete a customer with sales history. Deactivate instead.',
            ]);
        }

        $customer->delete();
    }

    /**
     * @return list<array{id: int, name: string, code: string, email: ?string, phone: ?string, company: ?string, customer_group_id: ?int}>
     */
    public function optionList(int $limit = 80): array
    {
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'code', 'email', 'phone', 'company', 'customer_group_id'])
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company' => $customer->company,
                'customer_group_id' => $customer->customer_group_id,
            ])
            ->all();
    }

    /**
     * Fill order/quote/POS fields from a CRM customer. Typed values win over the snapshot.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applySnapshot(array $data): array
    {
        $customerId = ! empty($data['customer_id']) ? (int) $data['customer_id'] : null;

        if ($customerId === null) {
            $data['customer_id'] = null;

            return $data;
        }

        $customer = Customer::query()->whereKey($customerId)->where('is_active', true)->first();

        if ($customer === null) {
            throw ValidationException::withMessages([
                'customer_id' => 'Select an active customer.',
            ]);
        }

        $typedName = trim((string) ($data['customer_name'] ?? ''));
        $walkIn = $typedName === '' || strcasecmp($typedName, 'Walk-in') === 0;

        $data['customer_id'] = $customer->id;
        $data['customer_name'] = $walkIn ? $customer->name : $typedName;
        $data['customer_email'] = ($data['customer_email'] ?? null) ?: $customer->email;
        $data['customer_phone'] = ($data['customer_phone'] ?? null) ?: $customer->phone;

        if (array_key_exists('customer_group_id', $data)) {
            $data['customer_group_id'] = $data['customer_group_id'] ?: $customer->customer_group_id;
        }

        return $data;
    }

    /**
     * Reuse a customer by email or phone, otherwise create one (storefront checkout).
     *
     * @param  array{name: string, email?: string|null, phone?: string|null, address?: string|null}  $data
     */
    public function matchOrCreateFromContact(array $data, ?int $userId = null): Customer
    {
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;

        $existing = ($email || $phone)
            ? Customer::query()
                ->where(function ($query) use ($email, $phone) {
                    if ($email) {
                        $query->orWhere('email', $email);
                    }
                    if ($phone) {
                        $query->orWhere('phone', $phone);
                    }
                })
                ->first()
            : null;

        if ($existing) {
            return $existing;
        }

        return $this->create([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $phone,
            'address' => $data['address'] ?? null,
            'is_active' => true,
        ], $userId);
    }

    /**
     * Customer 360 payload: contact, LTV, related leads, activities, and channel orders.
     *
     * @return array{
     *     customer: array<string, mixed>,
     *     stats: array{
     *         lifetime_value: string,
     *         currency: string,
     *         sales_orders: int,
     *         pos_orders: int,
     *         online_orders: int,
     *         open_follow_ups: int,
     *         leads: int
     *     },
     *     leads: list<array<string, mixed>>,
     *     activities: list<array<string, mixed>>,
     *     sales_orders: list<array<string, mixed>>,
     *     pos_orders: list<array<string, mixed>>,
     *     online_orders: list<array<string, mixed>>
     * }
     */
    public function profile(Customer $customer): array
    {
        $customer->loadCount('activities');

        $salesOrders = [];
        $posOrders = [];
        $onlineOrders = [];
        $salesTotal = '0';
        $posTotal = '0';
        $onlineTotal = '0';
        $salesCount = 0;
        $posCount = 0;
        $onlineCount = 0;
        $currency = 'BDT';

        if (Schema::hasTable('sales_orders')) {
            $salesQuery = SalesOrder::query()->where('customer_id', $customer->id);
            $salesCount = (clone $salesQuery)->count();
            $salesTotal = (string) ((clone $salesQuery)
                ->where('status', SalesOrderStatus::Confirmed->value)
                ->sum('grand_total'));
            $salesOrders = (clone $salesQuery)
                ->withCount('items')
                ->orderByDesc('created_at')
                ->limit(15)
                ->get()
                ->map(fn (SalesOrder $order) => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'currency' => $order->currency,
                    'grand_total' => (string) $order->grand_total,
                    'items_count' => $order->items_count,
                    'created_at' => $order->created_at?->toIso8601String(),
                ])
                ->all();
            $currency = $salesOrders[0]['currency'] ?? $currency;
        }

        if (Schema::hasTable('pos_orders')) {
            $posQuery = PosOrder::query()->where('customer_id', $customer->id);
            $posCount = (clone $posQuery)->count();
            $posTotal = (string) ((clone $posQuery)
                ->where('status', PosOrderStatus::Completed->value)
                ->sum('grand_total'));
            $posOrders = (clone $posQuery)
                ->withCount('items')
                ->orderByDesc('created_at')
                ->limit(15)
                ->get()
                ->map(fn (PosOrder $order) => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'currency' => $order->currency,
                    'grand_total' => (string) $order->grand_total,
                    'items_count' => $order->items_count,
                    'created_at' => $order->created_at?->toIso8601String(),
                ])
                ->all();
            $currency = $posOrders[0]['currency'] ?? $currency;
        }

        if (Schema::hasTable('online_orders')) {
            $onlineQuery = OnlineOrder::query()->where('customer_id', $customer->id);
            $onlineCount = (clone $onlineQuery)->count();
            $onlineTotal = (string) ((clone $onlineQuery)
                ->where('status', OnlineOrderStatus::Confirmed->value)
                ->sum('grand_total'));
            $onlineOrders = (clone $onlineQuery)
                ->withCount('items')
                ->orderByDesc('created_at')
                ->limit(15)
                ->get()
                ->map(fn (OnlineOrder $order) => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'currency' => $order->currency,
                    'grand_total' => (string) $order->grand_total,
                    'items_count' => $order->items_count,
                    'created_at' => $order->created_at?->toIso8601String(),
                ])
                ->all();
            $currency = $onlineOrders[0]['currency'] ?? $currency;
        }

        $leadsQuery = Lead::query()->where('converted_customer_id', $customer->id);
        $leadsCount = (clone $leadsQuery)->count();
        $leads = (clone $leadsQuery)
            ->with(['leadSource:id,name', 'assignedUser:id,name'])
            ->withCount('activities')
            ->orderByDesc('converted_at')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'source' => $lead->leadSource?->name ?? $lead->source,
                'stage' => $lead->stage->value,
                'stage_label' => $lead->stage->label(),
                'assigned_to_name' => $lead->assignedUser?->name,
                'converted_at' => $lead->converted_at?->toIso8601String(),
                'activities_count' => $lead->activities_count,
                'created_at' => $lead->created_at?->toIso8601String(),
            ])
            ->all();

        $activities = CrmActivity::query()
            ->with(['lead:id,name'])
            ->where('customer_id', $customer->id)
            ->orderByRaw('CASE WHEN completed_at IS NULL AND due_at IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('due_at')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (CrmActivity $activity) => [
                'id' => $activity->id,
                'type' => $activity->type->value,
                'type_label' => $activity->type->label(),
                'subject' => $activity->subject,
                'body' => $activity->body,
                'due_at' => $activity->due_at?->toIso8601String(),
                'completed_at' => $activity->completed_at?->toIso8601String(),
                'is_overdue' => $activity->completed_at === null
                    && $activity->due_at !== null
                    && $activity->due_at->isPast(),
                'lead_id' => $activity->lead_id,
                'lead_name' => $activity->lead?->name,
                'created_at' => $activity->created_at?->toIso8601String(),
            ])
            ->all();

        $openFollowUps = CrmActivity::query()
            ->where('customer_id', $customer->id)
            ->whereNull('completed_at')
            ->whereNotNull('due_at')
            ->count();

        $lifetimeValue = bcadd(bcadd($salesTotal, $posTotal, 2), $onlineTotal, 2);

        return [
            'customer' => $this->format($customer),
            'stats' => [
                'lifetime_value' => $lifetimeValue,
                'currency' => $currency,
                'sales_orders' => $salesCount,
                'pos_orders' => $posCount,
                'online_orders' => $onlineCount,
                'open_follow_ups' => $openFollowUps,
                'leads' => $leadsCount,
            ],
            'leads' => $leads,
            'activities' => $activities,
            'sales_orders' => $salesOrders,
            'pos_orders' => $posOrders,
            'online_orders' => $onlineOrders,
        ];
    }

    /**
     * @return array{id: int, name: string, code: string, email: ?string, phone: ?string, company: ?string, address: ?string, customer_group_id: ?int, customer_group_name: ?string, is_active: bool, notes: ?string, activities_count: int, created_at: ?string}
     */
    public function format(Customer $customer): array
    {
        $groupName = null;

        if ($customer->customer_group_id) {
            $groupName = DB::table('customer_groups')->where('id', $customer->customer_group_id)->value('name');
        }

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'code' => $customer->code,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'company' => $customer->company,
            'address' => $customer->address,
            'customer_group_id' => $customer->customer_group_id,
            'customer_group_name' => $groupName,
            'is_active' => $customer->is_active,
            'notes' => $customer->notes,
            'activities_count' => $customer->activities_count ?? $customer->activities()->count(),
            'created_at' => $customer->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    public function groupOptions(): array
    {
        return DB::table('customer_groups')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'code' => $row->code,
            ])
            ->all();
    }

    private function nextCode(): string
    {
        $seq = Customer::query()->lockForUpdate()->count() + 1;

        return 'CUS-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
