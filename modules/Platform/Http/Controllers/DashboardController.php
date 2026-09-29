<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Platform\Services\PlatformDashboardService;

class DashboardController extends Controller
{
    public function __invoke(PlatformDashboardService $dashboard): Response
    {
        return Inertia::render('Platform/Dashboard/Index', [
            ...$dashboard->overview(),
            'expiringWithinDays' => PlatformDashboardService::EXPIRING_WITHIN_DAYS,
        ]);
    }
}
