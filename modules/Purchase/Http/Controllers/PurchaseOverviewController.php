<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Purchase\Services\PurchaseOrderService;

class PurchaseOverviewController extends Controller
{
    public function index(PurchaseOrderService $orders): Response
    {
        return Inertia::render('Purchase/Overview/Index', [
            'stats' => $orders->overviewStats(),
            'recentOrders' => $orders->listPaginated(perPage: 8)->items(),
        ]);
    }
}
