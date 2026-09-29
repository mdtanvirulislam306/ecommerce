<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Platform\Services\ImpersonationService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a platform admin who is viewing a shop as someone else from changing that person's own login.
 */
class PreventDuringImpersonation
{
    public function __construct(private readonly ImpersonationService $impersonation) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_if(
            $this->impersonation->isImpersonating($request),
            403,
            'You are signed in as someone else. Their profile and password can only be changed by them.',
        );

        return $next($request);
    }
}
