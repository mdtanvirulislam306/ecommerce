<?php

namespace App\Http\Controllers;

use App\Core\Module\Middleware\EnsureModuleEnabled;
use App\Core\Module\ModuleManager;
use App\Core\Services\OwnerDashboardService;
use App\Core\Services\SalesManagerDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DashboardController extends Controller
{
    /**
     * Admin home. Sales Manager roles receive the sales payload. Everyone else
     * keeps OwnerDashboardService::forOwner(). Sales off uses the sales module gate.
     */
    public function __invoke(
        Request $request,
        OwnerDashboardService $ownerDashboard,
        SalesManagerDashboardService $salesDashboard,
        ModuleManager $modules,
        EnsureModuleEnabled $moduleGate,
    ): Response|HttpResponse {
        if ($request->user()?->isSalesManager()) {
            if (! $modules->enabled('sales')) {
                return $moduleGate->handle($request, fn () => response(''), 'sales');
            }

            return Inertia::render('Dashboard', $salesDashboard->overview());
        }

        return Inertia::render('Dashboard', $ownerDashboard->forOwner());
    }
}
