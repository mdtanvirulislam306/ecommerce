<?php

namespace App\Http\Controllers;

use App\Core\Services\OwnerDashboardService;
use App\Core\Services\SalesManagerDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Admin home. Sales Manager roles receive the sales payload. Everyone else
     * keeps OwnerDashboardService::forOwner().
     */
    public function __invoke(
        OwnerDashboardService $ownerDashboard,
        SalesManagerDashboardService $salesDashboard,
    ): Response {
        $payload = request()->user()?->isSalesManager()
            ? $salesDashboard->overview()
            : $ownerDashboard->forOwner();

        return Inertia::render('Dashboard', $payload);
    }
}
