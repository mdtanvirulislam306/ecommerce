<?php

namespace App\Http\Controllers;

use App\Core\Services\OwnerDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Owner overview. Other admin roles are not served from this action yet.
     */
    public function __invoke(OwnerDashboardService $dashboard): Response
    {
        return Inertia::render('Dashboard', $dashboard->forOwner());
    }
}
