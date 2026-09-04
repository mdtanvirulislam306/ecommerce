<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tasks\Services\TaskService;

class MyTaskController extends Controller
{
    public function index(Request $request, TaskService $tasks): Response
    {
        return Inertia::render('Tasks/MyTasks/Index', [
            'tasks' => $tasks->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
                ['assigned_to' => $request->user()?->id],
            ),
            'filters' => ['search' => $request->string('search')->toString()],
            'options' => [],
        ]);
    }
}
