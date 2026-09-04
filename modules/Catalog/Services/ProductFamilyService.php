<?php

namespace Modules\Catalog\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\ProductFamily;
use Modules\Catalog\Support\GeneratesUniqueSlug;

class ProductFamilyService extends Service
{
    use GeneratesUniqueSlug;

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return ProductFamily::query()
            ->withCount('products')
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): ProductFamily
    {
        return DB::transaction(function () use ($data) {
            return ProductFamily::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], ProductFamily::class),
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(ProductFamily $family, array $data): ProductFamily
    {
        return DB::transaction(function () use ($family, $data) {
            $family->update([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], ProductFamily::class, $family->id),
                'description' => $data['description'] ?? $family->description,
                'is_active' => $data['is_active'] ?? $family->is_active,
            ]);

            return $family->fresh();
        });
    }

    public function delete(ProductFamily $family): void
    {
        DB::transaction(fn () => $family->delete());
    }
}
