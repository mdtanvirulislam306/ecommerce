<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TaskCalendarController extends Controller
{
    public function index(): Response
    {
        $items = DB::table('tasks')
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->limit(100)
            ->get(['id', 'title', 'due_at', 'status']);

        return Inertia::render('Tasks/Calendar/Index', [
            'items' => $items,
        ]);
    }
}
