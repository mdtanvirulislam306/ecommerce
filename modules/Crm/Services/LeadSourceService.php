<?php

namespace Modules\Crm\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Crm\Models\LeadSource;

class LeadSourceService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return LeadSource::query()
            ->withCount('leads')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    public function options(): array
    {
        return LeadSource::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (LeadSource $source) => [
                'id' => $source->id,
                'name' => $source->name,
                'code' => $source->code,
            ])
            ->all();
    }

    public function create(array $data): LeadSource
    {
        return LeadSource::query()->create([
            'name' => $data['name'],
            'code' => filled($data['code'] ?? null) ? $data['code'] : $this->uniqueCode($data['name']),
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(LeadSource $source, array $data): LeadSource
    {
        $source->update([
            'name' => $data['name'],
            'code' => filled($data['code'] ?? null) ? $data['code'] : $source->code,
            'is_active' => $data['is_active'] ?? $source->is_active,
            'sort_order' => $data['sort_order'] ?? $source->sort_order,
        ]);

        return $source->fresh();
    }

    public function delete(LeadSource $source): void
    {
        if ($source->leads()->exists()) {
            throw ValidationException::withMessages([
                'source' => 'Cannot delete a source that is used by leads.',
            ]);
        }

        $source->delete();
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::upper(Str::slug($name, '_')) ?: 'SOURCE';
        $base = Str::limit($base, 36, '');
        $code = $base;
        $i = 2;

        while (LeadSource::query()->where('code', $code)->exists()) {
            $code = Str::limit($base, 36, '').'_'.$i;
            $i++;
        }

        return $code;
    }
}
