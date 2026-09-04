<?php

namespace Modules\Catalog\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Enums\AttributeInputType;
use Modules\Catalog\Enums\AttributeType;
use Modules\Catalog\Models\Attribute;
use Modules\Catalog\Models\AttributeOption;

class AttributeService extends Service
{
    public function listPaginated(?string $search = null, ?AttributeType $type = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Attribute::query()
            ->with(['options' => fn ($query) => $query->orderBy('sort_order')])
            ->withCount('options')
            ->when($type, fn ($query, $type) => $query->where('type', $type))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Attribute
    {
        return DB::transaction(function () use ($data) {
            $attribute = Attribute::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'type' => $data['type'] ?? AttributeType::Informational,
                'input_type' => $data['input_type'] ?? AttributeInputType::Select,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            if (! empty($data['options'])) {
                $this->syncOptions($attribute, $data['options']);
            }

            return $attribute->load('options');
        });
    }

    public function update(Attribute $attribute, array $data): Attribute
    {
        return DB::transaction(function () use ($attribute, $data) {
            $attribute->update([
                'name' => $data['name'],
                'code' => $data['code'],
                'type' => $data['type'] ?? $attribute->type,
                'input_type' => $data['input_type'] ?? $attribute->input_type,
                'is_active' => $data['is_active'] ?? $attribute->is_active,
                'sort_order' => $data['sort_order'] ?? $attribute->sort_order,
            ]);

            if (array_key_exists('options', $data)) {
                $this->syncOptions($attribute, $data['options'] ?? []);
            }

            return $attribute->fresh()->load('options');
        });
    }

    public function delete(Attribute $attribute): void
    {
        DB::transaction(fn () => $attribute->delete());
    }

    /**
     * @param  list<array{value: string, code?: string|null, sort_order?: int}>  $options
     */
    private function syncOptions(Attribute $attribute, array $options): void
    {
        $attribute->options()->delete();

        foreach ($options as $index => $option) {
            AttributeOption::query()->create([
                'attribute_id' => $attribute->id,
                'value' => $option['value'],
                'code' => $option['code'] ?? null,
                'sort_order' => $option['sort_order'] ?? $index,
            ]);
        }
    }
}
