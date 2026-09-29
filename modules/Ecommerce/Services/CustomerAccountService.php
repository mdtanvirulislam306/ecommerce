<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Crm\Services\CustomerService;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Models\CustomerAccount;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Models\OnlineOrderItem;

class CustomerAccountService extends Service
{
    public function __construct(private readonly CustomerService $customers) {}

    /**
     * @param  array{name: string, email: string, phone?: string|null, password: string}  $data
     */
    public function register(array $data): CustomerAccount
    {
        return DB::transaction(function () use ($data) {
            $customer = $this->customers->matchOrCreateFromContact([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);

            return CustomerAccount::query()->create([
                'customer_id' => $customer->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);
        });
    }

    /**
     * @param  array{name: string, email: string, phone?: string|null, default_address?: string|null, default_district?: string|null}  $data
     */
    public function updateProfile(CustomerAccount $account, array $data): void
    {
        $account->update($data);
    }

    /**
     * Only fills an empty address book, so checking out to a friend's address doesn't overwrite the saved one.
     */
    public function rememberAddress(CustomerAccount $account, ?string $address, ?string $district, ?string $phone): void
    {
        $account->fill(array_filter([
            'default_address' => blank($account->default_address) ? $address : null,
            'default_district' => blank($account->default_address) ? $district : null,
            'phone' => blank($account->phone) ? $phone : null,
        ], filled(...)));

        if ($account->isDirty()) {
            $account->save();
        }
    }

    /**
     * @return array{orders: int, spent: string, currency: string, member_since: string|null}
     */
    public function stats(CustomerAccount $account): array
    {
        $orders = OnlineOrder::query()->where('customer_account_id', $account->id);

        return [
            'orders' => (clone $orders)->count(),
            'spent' => number_format(
                (float) (clone $orders)->where('status', '!=', OnlineOrderStatus::Cancelled->value)->sum('grand_total'),
                2,
                '.',
                '',
            ),
            'currency' => (string) ((clone $orders)->latest('id')->value('currency') ?? 'BDT'),
            'member_since' => $account->created_at?->toIso8601String(),
        ];
    }

    public function orders(CustomerAccount $account, int $perPage = 8): LengthAwarePaginator
    {
        return OnlineOrder::query()
            ->where('customer_account_id', $account->id)
            ->with('items')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (OnlineOrder $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'payment_status' => $order->payment_status->value,
                'payment_status_label' => $order->payment_status->label(),
                'payment_method_label' => $order->payment_method->label(),
                'currency' => $order->currency,
                'grand_total' => (string) $order->grand_total,
                'items_count' => (int) $order->items->sum(fn (OnlineOrderItem $item) => (float) $item->quantity),
                'item_names' => $order->items->take(3)->pluck('name')->all(),
                'more_items' => max(0, $order->items->count() - 3),
                'created_at' => $order->created_at?->toIso8601String(),
                'tracking_url' => route('shop.orders.show', $order->access_token),
            ]);
    }

    /**
     * @return array{id: int, name: string, email: string, phone: string|null, default_address: string|null, default_district: string|null}
     */
    public function profile(CustomerAccount $account): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'email' => $account->email,
            'phone' => $account->phone,
            'default_address' => $account->default_address,
            'default_district' => $account->default_district,
        ];
    }
}
