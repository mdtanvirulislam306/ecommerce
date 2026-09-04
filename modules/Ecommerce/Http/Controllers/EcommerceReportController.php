<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Services\EcommerceReportService;

class EcommerceReportController extends Controller
{
    public function index(EcommerceReportService $service): Response
    {
        return Inertia::render('Ecommerce/Reports/Index', [
            'stats' => $service->overview(),
        ]);
    }
}
