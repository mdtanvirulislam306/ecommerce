<?php

namespace Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Support\Http\Requests\StoreCannedResponseRequest;
use Modules\Support\Http\Requests\UpdateCannedResponseRequest;
use Modules\Support\Models\CannedResponse;
use Modules\Support\Services\CannedResponseService;

class CannedResponseController extends Controller
{
    public function index(Request $request, CannedResponseService $svc): Response
    {
        return Inertia::render('Support/CannedResponses/Index', [
            'responses' => $svc->listPaginated(
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

    public function store(StoreCannedResponseRequest $request, CannedResponseService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateCannedResponseRequest $request, CannedResponse $cannedResponse, CannedResponseService $svc): RedirectResponse
    {
        $svc->update($cannedResponse, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(CannedResponse $cannedResponse, CannedResponseService $svc): RedirectResponse
    {
        $svc->delete($cannedResponse);

        return back()->with('success', 'Deleted.');
    }
}
