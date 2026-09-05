<?php

namespace Modules\Marketing\Services;

use App\Core\Module\ModuleManager;
use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Modules\Crm\Models\CustomerSegment;
use Modules\Crm\Services\CustomerSegmentService;
use Modules\Marketing\Models\Segment;

class SegmentService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Segment::query()
            ->with(['customerSegment' => fn ($query) => $query->withCount('customers')])
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
        $payload = $this->resolveCrmAudience($data);

        return Segment::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Segment $segment, array $data): Segment
    {
        $payload = $this->resolveCrmAudience(array_merge([
            'name' => $segment->name,
            'description' => $segment->description,
            'customer_segment_id' => $segment->customer_segment_id,
            'rules' => $segment->rules,
            'customer_count' => $segment->customer_count,
            'is_active' => $segment->is_active,
        ], $data));

        $segment->update($payload);

        return $segment->fresh(['customerSegment']);
    }

    public function delete(Segment $segment): void
    {
        $segment->delete();
    }

    /**
     * @return list<array{id: int, name: string, code: string, customers_count: int}>
     */
    public function crmSegmentOptions(): array
    {
        if (! $this->crmSegmentsAvailable()) {
            return [];
        }

        return app(CustomerSegmentService::class)->audienceOptions();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Segment $segment): array
    {
        $crm = $segment->customerSegment;
        $liveCount = $crm !== null
            ? (int) ($crm->customers_count ?? $crm->customers()->count())
            : (int) $segment->customer_count;

        return [
            'id' => $segment->id,
            'name' => $segment->name,
            'description' => $segment->description,
            'customer_segment_id' => $segment->customer_segment_id,
            'customer_segment_name' => $crm?->name,
            'customer_segment_code' => $crm?->code,
            'rules' => $segment->rules,
            'customer_count' => $liveCount,
            'is_linked_to_crm' => $segment->customer_segment_id !== null,
            'is_active' => $segment->is_active,
            'created_at' => $segment->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveCrmAudience(array $data): array
    {
        $customerSegmentId = ! empty($data['customer_segment_id'])
            ? (int) $data['customer_segment_id']
            : null;

        $count = (int) ($data['customer_count'] ?? 0);
        $rules = $data['rules'] ?? null;

        if ($customerSegmentId && $this->crmSegmentsAvailable()) {
            $crm = CustomerSegment::query()->withCount('customers')->find($customerSegmentId);
            if ($crm) {
                $count = (int) $crm->customers_count;
                $rules = [
                    'source' => 'crm',
                    'customer_segment_id' => $crm->id,
                    'customer_segment_code' => $crm->code,
                ];
            }
        } else {
            $customerSegmentId = null;
        }

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'customer_segment_id' => $customerSegmentId,
            'rules' => $rules,
            'customer_count' => $count,
            'is_active' => $data['is_active'] ?? true,
        ];
    }

    private function crmSegmentsAvailable(): bool
    {
        return app(ModuleManager::class)->enabled('crm')
            && Schema::hasTable('customer_segments');
    }
}
