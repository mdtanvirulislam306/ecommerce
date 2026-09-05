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
    /**
     * @param  array{search?: string|null, is_active?: string|null, sort?: string|null, direction?: string|null, per_page?: int}  $filters
     */
    public function listPaginated(array $filters = []): LengthAwarePaginator
    {
        $perPage = in_array((int) ($filters['per_page'] ?? 25), [10, 25, 50, 100], true)
            ? (int) $filters['per_page']
            : 25;
        $sort = in_array($filters['sort'] ?? '', ['name', 'code', 'sort_order', 'customers_count'], true)
            ? $filters['sort']
            : 'sort_order';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return CustomerSegment::query()
            ->with('customers:id')
            ->withCount('customers')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->when(
                in_array($filters['is_active'] ?? '', ['0', '1'], true),
                fn ($query) => $query->where('is_active', $filters['is_active'] === '1'),
            )
            ->orderBy($sort, $direction)
            ->when($sort !== 'name', fn ($query) => $query->orderBy('name'))
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
                'customer_ids' => $segment->customers->pluck('id')->all(),
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

    /**
     * @return list<array{id: int, name: string, code: string, customers_count: int}>
     */
    public function audienceOptions(): array
    {
        return CustomerSegment::query()
            ->where('is_active', true)
            ->withCount('customers')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (CustomerSegment $segment) => [
                'id' => $segment->id,
                'name' => $segment->name,
                'code' => $segment->code,
                'customers_count' => $segment->customers_count,
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
