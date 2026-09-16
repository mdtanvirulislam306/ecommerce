<?php

namespace App\Http\Middleware;

use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\TenantDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromHost
{
    public function __construct(private readonly TenantContext $tenants) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenants->clear();

        if ($request->is('platform') || $request->is('platform/*')) {
            $this->tenants->markPlatformRequest();

            return $next($request);
        }

        if (! Schema::hasTable('tenants') || ! Schema::hasTable('tenant_domains')) {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $domain = TenantDomain::query()
            ->with('tenant')
            ->where('domain', $host)
            ->where('is_active', true)
            ->first();

        if ($domain?->tenant) {
            $this->tenants->set($domain->tenant);

            return $next($request);
        }

        // Local / IP fallback: bind Default tenant so single-host Laragon keeps working.
        if ($this->isLocalHost($host)) {
            $default = Tenant::query()->where('slug', 'default')->first()
                ?? Tenant::query()->orderBy('id')->first();

            if ($default) {
                $this->tenants->set($default);
            }
        }

        return $next($request);
    }

    private function isLocalHost(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local')
            || filter_var($host, FILTER_VALIDATE_IP) !== false;
    }
}
