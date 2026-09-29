<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Platform\Services\ImpersonationService;

class ImpersonationSessionController extends Controller
{
    public function __construct(private readonly ImpersonationService $impersonation) {}

    public function store(Request $request, string $token): RedirectResponse
    {
        $user = $this->impersonation->redeem($token, $request);

        return redirect()->route('dashboard')->with('success', "You are now viewing the shop as {$user->name}.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $tenantId = $this->impersonation->leave($request);

        if ($tenantId === null) {
            return redirect()->route('dashboard');
        }

        return redirect()
            ->route('platform.tenants.show', $tenantId)
            ->with('success', 'You are back in the platform console.');
    }
}
