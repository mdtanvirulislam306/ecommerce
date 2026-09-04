<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Services\StockService;
use Modules\Inventory\Services\WarehouseService;

class InventoryOverviewController extends Controller
{
    public function index(StockService $stock, WarehouseService $warehouses): Response
    {
        return Inertia::render('Inventory/Overview/Index', [
            'stats' => $stock->overviewStats(),
            'warehouses' => $warehouses->options(),
        ]);
    }
}
