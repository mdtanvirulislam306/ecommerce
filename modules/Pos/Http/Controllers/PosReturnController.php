<?php

namespace Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Pos\Http\Requests\StorePosReturnRequest;
use Modules\Pos\Services\PosReturnService;

class PosReturnController extends Controller
{
    public function index(Request $request, PosReturnService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Pos/Returns/Index', [
            'returns' => $service->listPaginated($search ?: null, $perPage),
            'orders' => $service->returnableOrders(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StorePosReturnRequest $request, PosReturnService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user()->id);

        return back()->with('success', 'POS return recorded and stock restocked.');
    }
}
