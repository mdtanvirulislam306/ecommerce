<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Purchase\Services\PurchaseReportService;

class PurchaseReportController extends Controller
{
    public function index(PurchaseReportService $service): Response
    {
        return Inertia::render('Purchase/Reports/Index', $service->overview());
    }
}
