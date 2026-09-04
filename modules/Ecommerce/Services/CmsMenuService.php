<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\CmsMenu;

class CmsMenuService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return CmsMenu::query()
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): CmsMenu
    {
        return DB::transaction(fn () => CmsMenu::query()->create([
            'name' => $data['name'],
            'location' => $data['location'],
            'items' => $data['items'] ?? [],
            'is_active' => $data['is_active'] ?? true,
        ]));
    }

    public function update(CmsMenu $menu, array $data): CmsMenu
    {
        return DB::transaction(function () use ($menu, $data) {
            $menu->update([
                'name' => $data['name'],
                'location' => $data['location'],
                'items' => $data['items'] ?? $menu->items,
                'is_active' => $data['is_active'] ?? $menu->is_active,
            ]);

            return $menu->fresh();
        });
    }

    public function delete(CmsMenu $menu): void
    {
        DB::transaction(fn () => $menu->delete());
    }
}
