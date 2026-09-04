<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Services\JournalService;

class AccountingOverviewController extends Controller
{
    public function index(JournalService $journals): Response
    {
        return Inertia::render('Accounting/Overview/Index', [
            'stats' => $journals->overviewStats(),
            'recentJournals' => $journals->listPaginated(perPage: 8)->items(),
        ]);
    }
}
