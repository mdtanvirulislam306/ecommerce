<?php

namespace Modules\Catalog\Support;

use Illuminate\Support\Str;

trait GeneratesUniqueSlug
{
    protected function uniqueSlug(string $name, string $modelClass, ?int $ignoreId = null): string
    {
        return $this->uniquifySlug(Str::slug($name) ?: 'item', $modelClass, $ignoreId);
    }

    protected function uniquifySlug(string $slug, string $modelClass, ?int $ignoreId = null): string
    {
        $slug = Str::slug($slug) ?: 'item';
        $base = $slug;
        $counter = 1;

        while (
            $modelClass::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$this->paddedSuffix($counter);
            $counter++;
        }

        return $slug;
    }

    protected function paddedSuffix(int $n): string
    {
        return str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    }
}
