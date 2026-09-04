<?php

namespace Modules\Support\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Support\Models\CannedResponse;
use Modules\Support\Models\SupportCategory;

class CannedResponseService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return CannedResponse::query()
            ->with(['category:id,name'])
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (CannedResponse $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): CannedResponse
    {
        return CannedResponse::query()->create([
            'title' => $data['title'],
            'body' => $data['body'],
            'category_id' => $data['category_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(CannedResponse $row, array $data): CannedResponse
    {
        $row->update([
            'title' => $data['title'] ?? $row->title,
            'body' => $data['body'] ?? $row->body,
            'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $row->category_id,
            'is_active' => $data['is_active'] ?? $row->is_active,
        ]);

        return $row->fresh();
    }

    public function delete(CannedResponse $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(CannedResponse $row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'body' => $row->body,
            'category_id' => $row->category_id,
            'is_active' => $row->is_active,
            'category_name' => $row->category?->name,
        ];
    }

    public function formOptions(): array
    {
        return [
            'categories' => SupportCategory::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all(),
        ];
    }
}
