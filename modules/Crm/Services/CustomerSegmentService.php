<?php

namespace Modules\Crm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Crm\Models\Customer;
use Modules\Crm\Models\CustomerSegment;

class CustomerSegmentService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return CustomerSegment::query()
            ->withCount('customers')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (CustomerSegment $segment) => [
                'id' => $segment->id,
                'name' => $segment->name,
                'code' => $segment->code,
                'description' => $segment->description,
                'is_active' => $segment->is_active,
                'sort_order' => $segment->sort_order,
                'customers_count' => $segment->customers_count,
                'customer_ids' => $segment->customers()->pluck('customers.id')->all(),
            ]);
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    public function customerOptions(): array
    {
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'code'])
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
            ])
            ->all();
    }

    public function create(array $data): CustomerSegment
    {
        return DB::transaction(function () use ($data) {
            $segment = CustomerSegment::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $segment->customers()->sync($data['customer_ids'] ?? []);

            return $segment->fresh();
        });
    }

    public function update(CustomerSegment $segment, array $data): CustomerSegment
    {
        return DB::transaction(function () use ($segment, $data) {
            $segment->update([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? $segment->description,
                'is_active' => $data['is_active'] ?? $segment->is_active,
                'sort_order' => $data['sort_order'] ?? $segment->sort_order,
            ]);

            if (array_key_exists('customer_ids', $data)) {
                $segment->customers()->sync($data['customer_ids'] ?? []);
            }

            return $segment->fresh();
        });
    }

    public function delete(CustomerSegment $segment): void
    {
        if ($segment->customers()->exists()) {
            throw ValidationException::withMessages([
                'segment' => 'Detach customers before deleting this segment.',
            ]);
        }

        $segment->delete();
    }
}
