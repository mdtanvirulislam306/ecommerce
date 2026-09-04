<?php

namespace Modules\Workflow\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Workflow\Models\ApprovalRequest;

class ApprovalRequestService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return ApprovalRequest::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%");
                $inner->orWhere('status', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ApprovalRequest $row) => $this->format($row));
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): ApprovalRequest
    {
        return ApprovalRequest::query()->create([
            'title' => $data['title'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'requested_by' => $data['requested_by'] ?? null,
            'approver_id' => $data['approver_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(ApprovalRequest $row, array $data): ApprovalRequest
    {
        $row->update([
            'title' => array_key_exists('title', $data) ? $data['title'] : $row->title,
            'status' => array_key_exists('status', $data) ? $data['status'] : $row->status,
            'requested_by' => array_key_exists('requested_by', $data) ? $data['requested_by'] : $row->requested_by,
            'approver_id' => array_key_exists('approver_id', $data) ? $data['approver_id'] : $row->approver_id,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $row->notes,
        ]);

        return $row->fresh();
    }

    public function delete(ApprovalRequest $row): void
    {
        $row->delete();
    }

    /** @return array<string, mixed> */
    public function format(ApprovalRequest $row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'status' => $row->status,
            'requested_by' => $row->requested_by,
            'approver_id' => $row->approver_id,
            'notes' => $row->notes,
        ];
    }
}
