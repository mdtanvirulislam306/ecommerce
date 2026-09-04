<?php

namespace Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SupportReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Support/Reports/Index', [
            'stats' => [
                'total' => DB::table('support_tickets')->count(),
                'open' => DB::table('support_tickets')->where('status', 'open')->count(),
                'unassigned' => DB::table('support_tickets')->whereNull('assigned_to')->count(),
                'resolved' => DB::table('support_tickets')->where('status', 'resolved')->count(),
                'categories' => DB::table('support_categories')->count(),
                'canned_responses' => DB::table('support_canned_responses')->count(),
            ],
        ]);
    }
}
