<?php

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowOverviewController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Workflow/Overview/Index', [
            'stats' => [
                'workflows' => DB::table('workflows')->count(),
                'pending_approvals' => DB::table('approval_requests')->where('status', 'pending')->count(),
                'automation_rules' => DB::table('automation_rules')->count(),
                'scheduled_tasks' => DB::table('scheduled_tasks')->count(),
            ],
        ]);
    }
}
