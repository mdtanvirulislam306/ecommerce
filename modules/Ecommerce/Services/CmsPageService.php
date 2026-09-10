<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\CmsPage;
use Modules\Ecommerce\Services\PageBuilder\PageBuilderRegistry;

class CmsPageService extends Service
{
    public function __construct(private readonly PageBuilderRegistry $registry) {}

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return CmsPage::query()
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            }))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): CmsPage
    {
        return DB::transaction(fn () => CmsPage::query()->create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['slug'] ?? $data['title']),
            'body' => $data['body'] ?? null,
            'blocks' => $this->blocksFromInput($data),
            'is_published' => $data['is_published'] ?? false,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
        ]));
    }

    public function update(CmsPage $page, array $data): CmsPage
    {
        return DB::transaction(function () use ($page, $data) {
            $payload = [
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['slug'] ?? $data['title'], $page->id),
                'body' => $data['body'] ?? $page->body,
                'is_published' => $data['is_published'] ?? $page->is_published,
                'seo_title' => $data['seo_title'] ?? $page->seo_title,
                'seo_description' => $data['seo_description'] ?? $page->seo_description,
            ];

            if (array_key_exists('blocks', $data)) {
                $payload['blocks'] = $this->blocksFromInput($data, $page);
            }

            $page->update($payload);

            return $page->fresh();
        });
    }

    public function delete(CmsPage $page): void
    {
        DB::transaction(fn () => $page->delete());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{sections: list<array<string, mixed>>}
     */
    private function blocksFromInput(array $data, ?CmsPage $page = null): array
    {
        if (isset($data['blocks']) && is_array($data['blocks'])) {
            return $this->registry->normalizeDocument($data['blocks']);
        }

        if ($page !== null && is_array($page->blocks)) {
            return $this->registry->normalizeDocument($page->blocks);
        }

        if (filled($data['body'] ?? null)) {
            return $this->registry->documentFromLegacyBody((string) $data['body']);
        }

        return $this->registry->emptyDocument();
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
