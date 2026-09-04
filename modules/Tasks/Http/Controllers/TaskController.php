<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tasks\Http\Requests\StoreTaskRequest;
use Modules\Tasks\Http\Requests\UpdateTaskRequest;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Services\TaskService;

class TaskController extends Controller
{
    public function index(Request $request, TaskService $svc): Response
    {
        return Inertia::render('Tasks/AllTasks/Index', [
            'tasks' => $svc->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'options' => method_exists($svc, 'formOptions') ? $svc->formOptions() : [],
        ]);
    }

    public function store(StoreTaskRequest $request, TaskService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateTaskRequest $request, Task $task, TaskService $svc): RedirectResponse
    {
        $svc->update($task, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(Task $task, TaskService $svc): RedirectResponse
    {
        $svc->delete($task);

        return back()->with('success', 'Deleted.');
    }
}
