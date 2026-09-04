<?php

namespace Modules\Hrm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Hrm\Models\Employee;
use Modules\Hrm\Models\Leave;

class LeaveService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Leave::query()
            ->with(['employee:id,name,code'])
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('type', 'like', "%{$search}%");
                $inner->orWhere('status', 'like', "%{$search}%");
                $inner->orWhere('reason', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Leave $row) => $this->format($row));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Leave
    {
        return Leave::query()->create([
            'employee_id' => $data['employee_id'] ?? null,
            'type' => $data['type'] ?? null,
            'from_date' => $data['from_date'] ?? null,
            'to_date' => $data['to_date'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'reason' => $data['reason'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Leave $row, array $data): Leave
    {
        $row->update([
            'employee_id' => $data['employee_id'] ?? $row->employee_id,
            'type' => $data['type'] ?? $row->type,
            'from_date' => $data['from_date'] ?? $row->from_date,
            'to_date' => $data['to_date'] ?? $row->to_date,
            'status' => $data['status'] ?? $row->status,
            'reason' => $data['reason'] ?? $row->reason,
        ]);

        return $row->fresh();
    }

    public function delete(Leave $row): void
    {
        $row->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Leave $row): array
    {
        return [
            'id' => $row->id,
            'employee_id' => $row->employee_id,
            'type' => $row->type,
            'from_date' => $row->from_date?->toDateString(),
            'to_date' => $row->to_date?->toDateString(),
            'status' => $row->status,
            'reason' => $row->reason,
            'employee_name' => $row->employee?->name,
        ];
    }

    public function formOptions(): array
    {
        return [
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name'])->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])->all(),
            'types' => ['Annual', 'Sick', 'Unpaid', 'Casual'],
            'statuses' => [
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'approved', 'label' => 'Approved'],
                ['value' => 'rejected', 'label' => 'Rejected'],
            ],
        ];
    }
}
