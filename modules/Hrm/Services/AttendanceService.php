<?php

namespace Modules\Hrm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Hrm\Models\Attendance;
use Modules\Hrm\Models\Employee;

class AttendanceService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Attendance::query()
            ->with(['employee:id,name,code'])
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('status', 'like', "%{$search}%");
                $inner->orWhere('notes', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Attendance $row) => $this->format($row));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Attendance
    {
        return Attendance::query()->create([
            'employee_id' => $data['employee_id'] ?? null,
            'date' => $data['date'] ?? null,
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'status' => $data['status'] ?? 'present',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Attendance $row, array $data): Attendance
    {
        $row->update([
            'employee_id' => $data['employee_id'] ?? $row->employee_id,
            'date' => $data['date'] ?? $row->date,
            'check_in' => $data['check_in'] ?? $row->check_in,
            'check_out' => $data['check_out'] ?? $row->check_out,
            'status' => $data['status'] ?? $row->status,
            'notes' => $data['notes'] ?? $row->notes,
        ]);

        return $row->fresh();
    }

    public function delete(Attendance $row): void
    {
        $row->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Attendance $row): array
    {
        return [
            'id' => $row->id,
            'employee_id' => $row->employee_id,
            'date' => $row->date?->toDateString(),
            'check_in' => $row->check_in,
            'check_out' => $row->check_out,
            'status' => $row->status,
            'notes' => $row->notes,
            'employee_name' => $row->employee?->name,
        ];
    }

    public function formOptions(): array
    {
        return [
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name'])->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])->all(),
            'statuses' => [
                ['value' => 'present', 'label' => 'Present'],
                ['value' => 'absent', 'label' => 'Absent'],
                ['value' => 'late', 'label' => 'Late'],
                ['value' => 'half_day', 'label' => 'Half day'],
            ],
        ];
    }
}
