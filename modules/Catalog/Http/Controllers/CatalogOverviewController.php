<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Services\CatalogOverviewService;

class CatalogOverviewController extends Controller
{
    public function index(CatalogOverviewService $service): Response
    {
        return Inertia::render('Catalog/Overview/Index', [
            'stats' => $service->stats(),
            'recentProducts' => $service->recentProducts(),
        ]);
    }
}
