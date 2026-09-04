<?php

namespace Modules\Hrm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Hrm\Models\Department;

class DepartmentService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Department::query()
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Department $row) => $this->format($row));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Department
    {
        return Department::query()->create([
            'code' => $data['code'] ?? null,
            'name' => $data['name'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Department $row, array $data): Department
    {
        $row->update([
            'code' => $data['code'] ?? $row->code,
            'name' => $data['name'] ?? $row->name,
            'is_active' => $data['is_active'] ?? $row->is_active,
            'notes' => $data['notes'] ?? $row->notes,
        ]);

        return $row->fresh();
    }

    public function delete(Department $row): void
    {
        $row->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Department $row): array
    {
        return [
            'id' => $row->id,
            'code' => $row->code,
            'name' => $row->name,
            'is_active' => $row->is_active,
            'notes' => $row->notes,

        ];
    }
}
