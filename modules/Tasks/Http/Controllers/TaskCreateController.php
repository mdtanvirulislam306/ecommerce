<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tasks\Http\Requests\StoreTaskRequest;
use Modules\Tasks\Services\TaskService;

class TaskCreateController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Tasks/Create/Index');
    }

    public function store(StoreTaskRequest $request, TaskService $tasks): RedirectResponse
    {
        $tasks->create($request->validated());

        return redirect()->route('tasks.my-tasks.index')->with('success', 'Task created.');
    }
}
