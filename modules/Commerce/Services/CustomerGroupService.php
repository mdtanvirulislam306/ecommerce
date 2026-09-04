<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Commerce\Models\CustomerGroup;
use Modules\Commerce\Models\PriceList;

class CustomerGroupService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return CustomerGroup::query()
            ->with('priceList:id,name,code')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, PriceList>
     */
    public function activePriceLists(): Collection
    {
        return PriceList::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public function create(array $data): CustomerGroup
    {
        $priceListId = $data['price_list_id']
            ?? PriceList::query()->where('is_default', true)->value('id');

        return DB::transaction(fn () => CustomerGroup::query()->create([
            'name' => $data['name'],
            'code' => filled($data['code'] ?? null) ? $data['code'] : $this->uniqueCode($data['name']),
            'description' => $data['description'] ?? null,
            'price_list_id' => $priceListId,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]));
    }

    public function update(CustomerGroup $group, array $data): CustomerGroup
    {
        return DB::transaction(function () use ($group, $data) {
            $group->update([
                'name' => $data['name'],
                'code' => filled($data['code'] ?? null) ? $data['code'] : $group->code,
                'description' => $data['description'] ?? $group->description,
                'price_list_id' => array_key_exists('price_list_id', $data)
                    ? $data['price_list_id']
                    : $group->price_list_id,
                'is_active' => $data['is_active'] ?? $group->is_active,
                'sort_order' => $data['sort_order'] ?? $group->sort_order,
            ]);

            return $group->fresh();
        });
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::upper(Str::slug($name, '_')) ?: 'GROUP';
        $base = Str::limit($base, 36, '');
        $code = $base;
        $i = 2;

        while (CustomerGroup::query()->where('code', $code)->exists()) {
            $code = Str::limit($base, 36, '').'_'.$i;
            $i++;
        }

        return $code;
    }

    public function delete(CustomerGroup $group): void
    {
        DB::transaction(fn () => $group->delete());
    }
}
