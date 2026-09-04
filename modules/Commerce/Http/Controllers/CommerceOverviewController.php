<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Services\CommerceOverviewService;

class CommerceOverviewController extends Controller
{
    public function index(CommerceOverviewService $service): Response
    {
        return Inertia::render('Commerce/Overview/Index', [
            'stats' => $service->stats(),
        ]);
    }
}
