<?php

namespace Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Pos\Services\PosReportService;

class PosReportController extends Controller
{
    public function index(PosReportService $service): Response
    {
        return Inertia::render('Pos/Reports/Index', $service->overview());
    }
}
