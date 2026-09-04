<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crm\Services\CrmReportService;

class CrmReportController extends Controller
{
    public function index(CrmReportService $service): Response
    {
        return Inertia::render('Crm/Reports/Index', $service->overview());
    }
}
