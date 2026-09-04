<?php

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Reports\Services\ReportDashboardService;

class ReportController extends Controller
{
    public function overview(ReportDashboardService $reports): Response
    {
        return Inertia::render('Reports/Overview/Index', [
            'stats' => $reports->overview(),
        ]);
    }

    public function sales(ReportDashboardService $reports): Response
    {
        return Inertia::render('Reports/Sales/Index', [
            'stats' => $reports->sales(),
        ]);
    }

    public function purchase(ReportDashboardService $reports): Response
    {
        return Inertia::render('Reports/Purchase/Index', [
            'stats' => $reports->purchase(),
        ]);
    }

    public function inventory(ReportDashboardService $reports): Response
    {
        return Inertia::render('Reports/Inventory/Index', [
            'stats' => $reports->inventory(),
        ]);
    }

    public function crm(ReportDashboardService $reports): Response
    {
        return Inertia::render('Reports/Crm/Index', [
            'stats' => $reports->crm(),
        ]);
    }

    public function ecommerce(ReportDashboardService $reports): Response
    {
        return Inertia::render('Reports/Ecommerce/Index', [
            'stats' => $reports->ecommerce(),
        ]);
    }

    public function pos(ReportDashboardService $reports): Response
    {
        return Inertia::render('Reports/Pos/Index', [
            'stats' => $reports->pos(),
        ]);
    }

    public function accounting(ReportDashboardService $reports): Response
    {
        return Inertia::render('Reports/Accounting/Index', [
            'stats' => $reports->accounting(),
        ]);
    }

    public function hr(ReportDashboardService $reports): Response
    {
        return Inertia::render('Reports/Hr/Index', [
            'stats' => $reports->hr(),
        ]);
    }

    public function custom(ReportDashboardService $reports): Response
    {
        return Inertia::render('Reports/Custom/Index', [
            'stats' => $reports->custom(),
        ]);
    }

}
