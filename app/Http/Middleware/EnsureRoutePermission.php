<?php

namespace App\Http\Middleware;

use App\Core\Permission\PermissionRegistry;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    public function __construct(private readonly PermissionRegistry $permissions) {}

    /**
     * Staff may only reach a module route when one of their roles grants the route's permission key.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $route = $request->route();

        if (! $user instanceof User || $route === null || $user->hasFullShopAccess()) {
            return $next($request);
        }

        $key = $this->permissions->keyForRoute($route);

        if ($key !== null && ! $user->hasPermission($key)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
