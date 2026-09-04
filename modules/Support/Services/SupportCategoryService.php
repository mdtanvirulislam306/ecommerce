<?php

namespace Modules\Support\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Support\Models\SupportCategory;

class SupportCategoryService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return SupportCategory::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SupportCategory $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): SupportCategory
    {
        return SupportCategory::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(SupportCategory $row, array $data): SupportCategory
    {
        $row->update([
            'name' => $data['name'] ?? $row->name,
            'slug' => $data['slug'] ?? $row->slug,
            'is_active' => $data['is_active'] ?? $row->is_active,
        ]);

        return $row->fresh();
    }

    public function delete(SupportCategory $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(SupportCategory $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
            'is_active' => $row->is_active,
        ];
    }
}
