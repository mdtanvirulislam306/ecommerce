<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\CmsPage;

class CmsPageService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return CmsPage::query()
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            }))
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): CmsPage
    {
        return DB::transaction(fn () => CmsPage::query()->create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['slug'] ?? $data['title']),
            'body' => $data['body'] ?? null,
            'is_published' => $data['is_published'] ?? false,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
        ]));
    }

    public function update(CmsPage $page, array $data): CmsPage
    {
        return DB::transaction(function () use ($page, $data) {
            $page->update([
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['slug'] ?? $data['title'], $page->id),
                'body' => $data['body'] ?? $page->body,
                'is_published' => $data['is_published'] ?? $page->is_published,
                'seo_title' => $data['seo_title'] ?? $page->seo_title,
                'seo_description' => $data['seo_description'] ?? $page->seo_description,
            ]);

            return $page->fresh();
        });
    }

    public function delete(CmsPage $page): void
    {
        DB::transaction(fn () => $page->delete());
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'page';
        $slug = $base;
        $counter = 1;

        while (CmsPage::query()
            ->when($ignoreId, fn ($query, $ignoreId) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
