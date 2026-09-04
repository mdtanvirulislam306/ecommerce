<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TaskTimelineController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Tasks/Timeline/Index', [
            'items' => DB::table('tasks')->orderByDesc('created_at')->limit(50)->get(['id', 'title', 'status', 'created_at']),
        ]);
    }
}
