<?php

namespace Modules\Catalog\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Collection;
use Modules\Catalog\Support\GeneratesUniqueSlug;

class CollectionService extends Service
{
    use GeneratesUniqueSlug;

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Collection::query()
            ->withCount('products')
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Collection
    {
        return DB::transaction(function () use ($data) {
            return Collection::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], Collection::class),
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    public function update(Collection $collection, array $data): Collection
    {
        return DB::transaction(function () use ($collection, $data) {
            $collection->update([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], Collection::class, $collection->id),
                'description' => $data['description'] ?? $collection->description,
                'is_active' => $data['is_active'] ?? $collection->is_active,
                'sort_order' => $data['sort_order'] ?? $collection->sort_order,
            ]);

            return $collection->fresh();
        });
    }

    public function delete(Collection $collection): void
    {
        DB::transaction(fn () => $collection->delete());
    }
}
