<?php

namespace Modules\Marketing\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Marketing\Models\Segment;

class SegmentService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Segment::query()
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Segment $segment) => $this->format($segment));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Segment
    {
        return Segment::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'rules' => $data['rules'] ?? null,
            'customer_count' => (int) ($data['customer_count'] ?? 0),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Segment $segment, array $data): Segment
    {
        $segment->update([
            'name' => $data['name'] ?? $segment->name,
            'description' => $data['description'] ?? $segment->description,
            'rules' => $data['rules'] ?? $segment->rules,
            'customer_count' => $data['customer_count'] ?? $segment->customer_count,
            'is_active' => $data['is_active'] ?? $segment->is_active,
        ]);

        return $segment->fresh();
    }

    public function delete(Segment $segment): void
    {
        $segment->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Segment $segment): array
    {
        return [
            'id' => $segment->id,
            'name' => $segment->name,
            'description' => $segment->description,
            'rules' => $segment->rules,
            'customer_count' => $segment->customer_count,
            'is_active' => $segment->is_active,
            'created_at' => $segment->created_at?->toIso8601String(),
        ];
    }
}
