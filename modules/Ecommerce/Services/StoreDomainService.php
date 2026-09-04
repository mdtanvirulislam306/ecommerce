<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\StoreDomain;

class StoreDomainService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return StoreDomain::query()
            ->when($search, fn ($query, $search) => $query->where('domain', 'like', "%{$search}%"))
            ->orderByDesc('is_primary')
            ->orderBy('domain')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): StoreDomain
    {
        return DB::transaction(function () use ($data) {
            if ($data['is_primary'] ?? false) {
                StoreDomain::query()->update(['is_primary' => false]);
            }

            return StoreDomain::query()->create([
                'domain' => $data['domain'],
                'is_primary' => $data['is_primary'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'ssl_status' => $data['ssl_status'] ?? null,
            ]);
        });
    }

    public function update(StoreDomain $domain, array $data): StoreDomain
    {
        return DB::transaction(function () use ($domain, $data) {
            if ($data['is_primary'] ?? false) {
                StoreDomain::query()->where('id', '!=', $domain->id)->update(['is_primary' => false]);
            }

            $domain->update([
                'domain' => $data['domain'],
                'is_primary' => $data['is_primary'] ?? $domain->is_primary,
                'is_active' => $data['is_active'] ?? $domain->is_active,
                'ssl_status' => $data['ssl_status'] ?? $domain->ssl_status,
            ]);

            return $domain->fresh();
        });
    }

    public function delete(StoreDomain $domain): void
    {
        DB::transaction(fn () => $domain->delete());
    }
}
