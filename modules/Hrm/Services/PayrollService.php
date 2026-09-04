<?php

namespace Modules\Hrm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Hrm\Models\Employee;
use Modules\Hrm\Models\Payroll;

class PayrollService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Payroll::query()
            ->with(['employee:id,name,code'])
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('period', 'like', "%{$search}%");
                $inner->orWhere('status', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Payroll $row) => $this->format($row));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Payroll
    {
        return Payroll::query()->create([
            'employee_id' => $data['employee_id'] ?? null,
            'period' => $data['period'] ?? null,
            'amount' => $data['amount'] ?? 0,
            'status' => $data['status'] ?? 'draft',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Payroll $row, array $data): Payroll
    {
        $row->update([
            'employee_id' => $data['employee_id'] ?? $row->employee_id,
            'period' => $data['period'] ?? $row->period,
            'amount' => $data['amount'] ?? $row->amount,
            'status' => $data['status'] ?? $row->status,
            'notes' => $data['notes'] ?? $row->notes,
        ]);

        return $row->fresh();
    }

    public function delete(Payroll $row): void
    {
        $row->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Payroll $row): array
    {
        return [
            'id' => $row->id,
            'employee_id' => $row->employee_id,
            'period' => $row->period,
            'amount' => $row->amount,
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
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'paid', 'label' => 'Paid'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ];
    }
}
