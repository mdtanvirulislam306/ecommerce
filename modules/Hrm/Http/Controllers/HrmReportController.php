<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HrmReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Hrm/Reports/Index', [
            'stats' => [
                'employees' => DB::table('hrm_employees')->count(),
                'active_employees' => DB::table('hrm_employees')->where('is_active', true)->count(),
                'departments' => DB::table('hrm_departments')->count(),
                'attendance_today' => DB::table('hrm_attendances')->whereDate('date', now()->toDateString())->count(),
                'pending_leave' => DB::table('hrm_leaves')->where('status', 'pending')->count(),
                'payroll_drafts' => DB::table('hrm_payrolls')->where('status', 'draft')->count(),
            ],
        ]);
    }
}
