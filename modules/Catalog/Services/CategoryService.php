<?php

namespace Modules\Catalog\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Support\GeneratesUniqueSlug;

class CategoryService extends Service
{
    use GeneratesUniqueSlug;

    public function listPaginated(?string $search = null, ?int $parentId = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Category::query()
            ->with('parent:id,name')
            ->when($parentId !== null, fn ($query) => $query->where('parent_id', $parentId))
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tree(?bool $activeOnly = true): array
    {
        $categories = Category::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug', 'is_active', 'sort_order']);

        return $this->buildTree($categories);
    }

    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            return Category::query()->create([
                'parent_id' => $data['parent_id'] ?? null,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], Category::class),
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $category->update([
                'parent_id' => $data['parent_id'] ?? $category->parent_id,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], Category::class, $category->id),
                'description' => $data['description'] ?? $category->description,
                'is_active' => $data['is_active'] ?? $category->is_active,
                'sort_order' => $data['sort_order'] ?? $category->sort_order,
            ]);

            return $category->fresh();
        });
    }

    public function delete(Category $category): void
    {
        DB::transaction(fn () => $category->delete());
    }

    /**
     * @param  SupportCollection<int, Category>  $categories
     * @return list<array<string, mixed>>
     */
    private function buildTree(SupportCollection $categories, ?int $parentId = null): array
    {
        return $categories
            ->where('parent_id', $parentId)
            ->values()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
                'children' => $this->buildTree($categories, $category->id),
            ])
            ->all();
    }
}
