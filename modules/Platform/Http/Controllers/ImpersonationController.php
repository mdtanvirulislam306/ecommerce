<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Platform\Services\ImpersonationService;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationController extends Controller
{
    public function store(Request $request, Tenant $tenant, int $user, ImpersonationService $impersonation): Response
    {
        $target = User::query()->where('tenant_id', $tenant->id)->findOrFail($user);

        return Inertia::location($impersonation->start($request->user(), $tenant, $target, $request));
    }
}
