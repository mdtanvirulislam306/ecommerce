<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crm\Services\LeadService;

class CrmOverviewController extends Controller
{
    public function index(LeadService $leads): Response
    {
        return Inertia::render('Crm/Overview/Index', [
            'stats' => $leads->overviewStats(),
            'recentLeads' => $leads->listPaginated(perPage: 8)->items(),
        ]);
    }
}
