<?php

namespace Modules\Hrm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Hrm\Models\Employee;

class EmployeeService extends Service
{
    /**
     * @return array{employees: int, active: int, departments: int}
     */
    public function overviewStats(): array
    {
        return [
            'employees' => Employee::query()->count(),
            'active' => Employee::query()->where('is_active', true)->count(),
            'departments' => Employee::query()->whereNotNull('department')->distinct('department')->count('department'),
        ];
    }

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Employee::query()
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Employee $employee) => $this->format($employee));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee
    {
        return Employee::query()->create([
            'code' => $data['code'] ?? $this->nextCode(),
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
            'designation' => $data['designation'] ?? null,
            'hired_at' => $data['hired_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        $employee->update([
            'code' => $data['code'] ?? $employee->code,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
            'designation' => $data['designation'] ?? null,
            'hired_at' => $data['hired_at'] ?? null,
            'is_active' => $data['is_active'] ?? $employee->is_active,
            'notes' => $data['notes'] ?? null,
        ]);

        return $employee->fresh();
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'code' => $employee->code,
            'name' => $employee->name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'department' => $employee->department,
            'designation' => $employee->designation,
            'hired_at' => $employee->hired_at?->toDateString(),
            'is_active' => $employee->is_active,
            'notes' => $employee->notes,
        ];
    }

    private function nextCode(): string
    {
        $n = Employee::query()->count() + 1;

        return 'EMP-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}
