<?php

namespace Modules\Hrm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Hrm\Models\Designation;
use Modules\Hrm\Models\Employee;
use Modules\Hrm\Models\SalaryStructure;

class SalaryStructureService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return SalaryStructure::query()
            ->with(['employee:id,name', 'designation:id,name'])
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SalaryStructure $row) => $this->format($row));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SalaryStructure
    {
        return SalaryStructure::query()->create([
            'name' => $data['name'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'designation_id' => $data['designation_id'] ?? null,
            'components' => is_string($data['components'] ?? null) ? json_decode($data['components'], true) : ($data['components'] ?? []),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SalaryStructure $row, array $data): SalaryStructure
    {
        $row->update([
            'name' => $data['name'] ?? $row->name,
            'employee_id' => $data['employee_id'] ?? $row->employee_id,
            'designation_id' => $data['designation_id'] ?? $row->designation_id,
            'components' => $data['components'] ?? $row->components,
            'is_active' => $data['is_active'] ?? $row->is_active,
        ]);

        return $row->fresh();
    }

    public function delete(SalaryStructure $row): void
    {
        $row->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(SalaryStructure $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'employee_id' => $row->employee_id,
            'designation_id' => $row->designation_id,
            'components' => $row->components,
            'is_active' => $row->is_active,
            'employee_name' => $row->employee?->name,
            'designation_name' => $row->designation?->name,
            'components_json' => json_encode($row->components ?? []),
        ];
    }

    public function formOptions(): array
    {
        return [
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name'])->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])->all(),
            'designations' => Designation::query()->orderBy('name')->get(['id', 'name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->all(),
        ];
    }
}
