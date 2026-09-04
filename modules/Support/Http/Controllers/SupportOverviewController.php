<?php

namespace Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Support\Services\TicketService;

class SupportOverviewController extends Controller
{
    public function index(TicketService $tickets): Response
    {
        return Inertia::render('Support/Overview/Index', [
            'stats' => $tickets->overviewStats(),
            'recent' => $tickets->listPaginated(perPage: 8)->items(),
        ]);
    }
}
