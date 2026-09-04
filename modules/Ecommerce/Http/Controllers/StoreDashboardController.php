<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Services\StoreDashboardService;

class StoreDashboardController extends Controller
{
    public function index(StoreDashboardService $service): Response
    {
        return Inertia::render('Ecommerce/Store/Dashboard', [
            'stats' => $service->stats(),
        ]);
    }
}
