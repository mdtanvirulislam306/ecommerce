<?php

namespace App\Core\Support;

use Illuminate\Database\Eloquent\Builder;

final class DocumentNumber
{
    /**
     * Next daily number such as "WEB-20260929-0007". It continues from the highest number issued today,
     * so deleting a document never makes the sequence hand out a number that already exists.
     */
    public static function next(Builder $query, string $prefix, string $column = 'number', int $padding = 4): string
    {
        $dailyPrefix = $prefix.'-'.now()->format('Ymd').'-';

        $last = (clone $query)
            ->where($column, 'like', $dailyPrefix.'%')
            ->lockForUpdate()
            ->orderByRaw('LENGTH('.$column.') DESC')
            ->orderByDesc($column)
            ->value($column);

        $sequence = $last === null ? 1 : ((int) substr($last, strlen($dailyPrefix))) + 1;

        return $dailyPrefix.str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT);
    }
}
