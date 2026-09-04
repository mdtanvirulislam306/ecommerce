<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Sales\Services\SalesOrderService;

class SalesOverviewController extends Controller
{
    public function index(SalesOrderService $service): Response
    {
        return Inertia::render('Sales/Overview/Index', [
            'stats' => $service->overviewStats(),
            'recentOrders' => $service->listPaginated(perPage: 8)->items(),
        ]);
    }
}
