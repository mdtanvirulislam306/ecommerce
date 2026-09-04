<?php

namespace App\Core\Module\Middleware;

use App\Core\Module\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(private ModuleManager $modules) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $code): Response
    {
        if ($this->modules->enabled($code)) {
            return $next($request);
        }

        $manifest = $this->modules->get($code);

        if ($request->expectsJson()) {
            abort(403, "Module [{$code}] is not enabled on your plan.");
        }

        return Inertia::render('Billing/Upgrade', [
            'module' => [
                'code' => $code,
                'name' => $manifest?->name ?? $code,
                'description' => $manifest?->description ?? '',
            ],
        ])->toResponse($request)->setStatusCode(403);
    }
}
