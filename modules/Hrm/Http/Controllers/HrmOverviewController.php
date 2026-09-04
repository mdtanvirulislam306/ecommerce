<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hrm\Services\EmployeeService;

class HrmOverviewController extends Controller
{
    public function index(EmployeeService $employees): Response
    {
        return Inertia::render('Hrm/Overview/Index', [
            'stats' => $employees->overviewStats(),
            'recent' => $employees->listPaginated(perPage: 8)->items(),
        ]);
    }
}
