<?php

namespace App\Http\Controllers;

use App\Core\Services\SalesManagerDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class SalesManagerDashboardController extends Controller
{
    /**
     * Sales Manager overview. Stays on the admin auth stack, not `module:sales`,
     * so a shop with Sales disabled still receives an empty payload.
     */
    public function __invoke(SalesManagerDashboardService $dashboard): Response
    {
        return Inertia::render('SalesManagerDashboard', $dashboard->overview());
    }
}
