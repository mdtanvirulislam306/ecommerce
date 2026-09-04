<?php

namespace Modules\Hrm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Hrm\Models\Department;
use Modules\Hrm\Models\Designation;

class DesignationService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Designation::query()
            ->with(['department:id,name'])
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                $inner->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Designation $row) => $this->format($row));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Designation
    {
        return Designation::query()->create([
            'code' => $data['code'] ?? null,
            'name' => $data['name'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Designation $row, array $data): Designation
    {
        $row->update([
            'code' => $data['code'] ?? $row->code,
            'name' => $data['name'] ?? $row->name,
            'department_id' => $data['department_id'] ?? $row->department_id,
            'is_active' => $data['is_active'] ?? $row->is_active,
        ]);

        return $row->fresh();
    }

    public function delete(Designation $row): void
    {
        $row->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Designation $row): array
    {
        return [
            'id' => $row->id,
            'code' => $row->code,
            'name' => $row->name,
            'department_id' => $row->department_id,
            'is_active' => $row->is_active,
            'department_name' => $row->department?->name,
        ];
    }

    public function formOptions(): array
    {
        return [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->all(),
        ];
    }
}
