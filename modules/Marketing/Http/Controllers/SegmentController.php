<?php

namespace Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Marketing\Http\Requests\StoreSegmentRequest;
use Modules\Marketing\Http\Requests\UpdateSegmentRequest;
use Modules\Marketing\Models\Segment;
use Modules\Marketing\Services\SegmentService;

class SegmentController extends Controller
{
    public function index(Request $request, SegmentService $segments): Response
    {
        return Inertia::render('Marketing/Segments/Index', [
            'segments' => $segments->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'crmSegments' => $segments->crmSegmentOptions(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
        ]);
    }

    public function store(StoreSegmentRequest $request, SegmentService $segments): RedirectResponse
    {
        $segments->create($request->validated());

        return back()->with('success', 'Segment created.');
    }

    public function update(UpdateSegmentRequest $request, Segment $segment, SegmentService $segments): RedirectResponse
    {
        $segments->update($segment, $request->validated());

        return back()->with('success', 'Segment updated.');
    }

    public function destroy(Segment $segment, SegmentService $segments): RedirectResponse
    {
        $segments->delete($segment);

        return back()->with('success', 'Segment deleted.');
    }
}
