<?php

namespace Modules\Crm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Crm\Models\Customer;

class CustomerService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Customer::query()
            ->withCount('activities')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
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

        $customer->delete();
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
