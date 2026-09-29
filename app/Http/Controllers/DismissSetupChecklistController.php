<?php

namespace App\Http\Controllers;

use App\Core\Services\SetupChecklistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DismissSetupChecklistController extends Controller
{
    /**
     * Dismiss the first-run checklist for the current shop.
     *
     * The shop owner has no assigned role. Sales Manager, checked with the
     * same role gate as the owner dashboard, and any other assigned role are
     * refused. The request body is ignored.
     */
    public function __invoke(Request $request, SetupChecklistService $checklist): RedirectResponse
    {
        $user = $request->user();

        if ($user === null || $user->isSalesManager() || $user->roles()->exists()) {
            abort(403);
        }

        $checklist->dismissForCurrentTenant();

        return back(fallback: route('dashboard'));
    }
}
