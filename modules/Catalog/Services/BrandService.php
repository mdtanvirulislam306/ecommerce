<?php

namespace Modules\Catalog\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Support\GeneratesUniqueSlug;

class BrandService extends Service
{
    use GeneratesUniqueSlug;

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Brand::query()
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Brand
    {
        return DB::transaction(function () use ($data) {
            return Brand::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], Brand::class),
                'logo_path' => $data['logo_path'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    public function update(Brand $brand, array $data): Brand
    {
        return DB::transaction(function () use ($brand, $data) {
            $brand->update([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], Brand::class, $brand->id),
                'logo_path' => $data['logo_path'] ?? $brand->logo_path,
                'is_active' => $data['is_active'] ?? $brand->is_active,
                'sort_order' => $data['sort_order'] ?? $brand->sort_order,
            ]);

            return $brand->fresh();
        });
    }

    public function delete(Brand $brand): void
    {
        DB::transaction(fn () => $brand->delete());
    }
}
