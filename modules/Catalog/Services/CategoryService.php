<?php

namespace Modules\Catalog\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            ->withQueryString()
            ->through(fn (Category $category) => $this->formatForAdmin($category));
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

    /**
     * @param  array{
     *     parent_id?: int|null,
     *     name: string,
     *     slug?: string|null,
     *     description?: string|null,
     *     media_library_id?: int|null,
     *     is_active?: bool,
     *     sort_order?: int
     * }  $data
     */
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $mediaLibraryId = $this->nullableId($data['media_library_id'] ?? null);
            $imagePath = $this->copyFromMediaLibrary($mediaLibraryId, 'categories');

            return Category::query()->create([
                'parent_id' => $data['parent_id'] ?? null,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], Category::class),
                'description' => $data['description'] ?? null,
                'media_library_id' => $mediaLibraryId,
                'image_path' => $imagePath,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    /**
     * @param  array{
     *     parent_id?: int|null,
     *     name: string,
     *     slug?: string|null,
     *     description?: string|null,
     *     media_library_id?: int|null,
     *     clear_image?: bool,
     *     is_active?: bool,
     *     sort_order?: int
     * }  $data
     */
    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $clearImage = (bool) ($data['clear_image'] ?? false);
            $mediaLibraryId = array_key_exists('media_library_id', $data)
                ? $this->nullableId($data['media_library_id'])
                : $category->media_library_id;
            $imagePath = $category->image_path;

            if ($clearImage) {
                $this->deleteStoredImage($category->image_path);
                $mediaLibraryId = null;
                $imagePath = null;
            } elseif (array_key_exists('media_library_id', $data) && $mediaLibraryId !== $category->media_library_id) {
                $this->deleteStoredImage($category->image_path);
                $imagePath = $this->copyFromMediaLibrary($mediaLibraryId, 'categories');
            }

            $category->update([
                'parent_id' => $data['parent_id'] ?? $category->parent_id,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], Category::class, $category->id),
                'description' => $data['description'] ?? $category->description,
                'media_library_id' => $mediaLibraryId,
                'image_path' => $imagePath,
                'is_active' => $data['is_active'] ?? $category->is_active,
                'sort_order' => $data['sort_order'] ?? $category->sort_order,
            ]);

            return $category->fresh();
        });
    }

    public function delete(Category $category): void
    {
        DB::transaction(function () use ($category) {
            $this->deleteStoredImage($category->image_path);
            $category->delete();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function formatForAdmin(Category $category): array
    {
        return [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'parent' => $category->parent ? [
                'id' => $category->parent->id,
                'name' => $category->parent->name,
            ] : null,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'media_library_id' => $category->media_library_id,
            'image_path' => $category->image_path,
            'image_url' => $category->image_path ? '/storage/'.$category->image_path : null,
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
        ];
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

    private function copyFromMediaLibrary(?int $mediaLibraryId, string $directory): ?string
    {
        if ($mediaLibraryId === null || ! Schema::hasTable('media_library_items')) {
            return null;
        }

        $item = DB::table('media_library_items')->where('id', $mediaLibraryId)->first();
        if ($item === null || blank($item->path)) {
            return null;
        }

        $disk = $item->disk ?: 'public';
        if (! Storage::disk($disk)->exists($item->path)) {
            return null;
        }

        $extension = pathinfo((string) $item->path, PATHINFO_EXTENSION) ?: 'jpg';
        $dest = trim($directory, '/').'/'.uniqid('lib_', true).'.'.$extension;
        Storage::disk('public')->put($dest, Storage::disk($disk)->get($item->path));

        return $dest;
    }

    private function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
