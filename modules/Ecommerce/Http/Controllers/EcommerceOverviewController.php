<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Services\OnlineOrderService;
use Modules\Ecommerce\Services\OnlineProductService;

class EcommerceOverviewController extends Controller
{
    public function index(OnlineOrderService $orders, OnlineProductService $products): Response
    {
        return Inertia::render('Ecommerce/Overview/Index', [
            'orderStats' => $orders->overviewStats(),
            'publicationStats' => $products->publicationStats(),
            'recentOrders' => $orders->listPaginated(perPage: 8)->items(),
        ]);
    }
}
