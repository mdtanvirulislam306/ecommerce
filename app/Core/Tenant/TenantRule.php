<?php

namespace App\Core\Tenant;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

final class TenantRule
{
    /**
     * Unique rule constrained to the current shop, so two shops may reuse the same code or slug.
     */
    public static function unique(string $table, string $column = 'NULL'): Unique
    {
        $rule = Rule::unique($table, $column);
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId !== null) {
            $rule->where('tenant_id', $tenantId);
        }

        return $rule;
    }

    /**
     * Exists rule constrained to the current shop, so ids belonging to another shop are rejected.
     */
    public static function exists(string $table, string $column = 'NULL'): Exists
    {
        $rule = Rule::exists($table, $column);
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId !== null) {
            $rule->where('tenant_id', $tenantId);
        }

        return $rule;
    }
}
