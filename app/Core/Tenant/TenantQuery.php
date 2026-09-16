<?php

namespace App\Core\Tenant;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;

final class TenantQuery
{
    /**
     * Constrain a query-builder query to the current tenant when tenancy is active.
     */
    public static function constrain(Builder $query, string $table): Builder
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null || ! Schema::hasColumn($table, 'tenant_id')) {
            return $query;
        }

        return $query->where($table.'.tenant_id', $tenantId);
    }
}
