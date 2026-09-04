<?php

namespace Modules\Purchase\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Modules\Purchase\Models\Supplier;

class SupplierService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Supplier::query()
            ->with('group:id,name,code')
            ->withCount('purchaseOrders')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Supplier $supplier) => [
                ...$supplier->toArray(),
                'group_name' => $supplier->group?->name,
            ]);
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    public function options(): array
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'code' => $supplier->code,
            ])
            ->all();
    }

    public function create(array $data): Supplier
    {
        return Supplier::query()->create([
            'supplier_group_id' => $data['supplier_group_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update([
            'supplier_group_id' => array_key_exists('supplier_group_id', $data)
                ? $data['supplier_group_id']
                : $supplier->supplier_group_id,
            'name' => $data['name'],
            'code' => $data['code'],
            'email' => $data['email'] ?? $supplier->email,
            'phone' => $data['phone'] ?? $supplier->phone,
            'address' => $data['address'] ?? $supplier->address,
            'is_active' => $data['is_active'] ?? $supplier->is_active,
            'sort_order' => $data['sort_order'] ?? $supplier->sort_order,
        ]);

        return $supplier->fresh();
    }

    public function delete(Supplier $supplier): void
    {
        if ($supplier->purchaseOrders()->exists()) {
            throw ValidationException::withMessages([
                'supplier' => 'Cannot delete a supplier with purchase orders.',
            ]);
        }

        $supplier->delete();
    }
}
