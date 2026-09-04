<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Sales\Services\SalesReportService;

class SalesReportController extends Controller
{
    public function index(SalesReportService $reports): Response
    {
        return Inertia::render('Sales/Reports/Index', [
            'stats' => $reports->overview(),
        ]);
    }
}
