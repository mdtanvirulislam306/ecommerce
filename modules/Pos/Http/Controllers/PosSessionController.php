<?php

namespace Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Pos\Http\Requests\StorePosCashMovementRequest;
use Modules\Pos\Services\PosSessionService;

class PosSessionController extends Controller
{
    public function open(Request $request, PosSessionService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Pos/Sessions/Open', [
            'sessions' => $service->listOpen($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function history(Request $request, PosSessionService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Pos/Sessions/History', [
            'sessions' => $service->listHistory($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function cash(Request $request, PosSessionService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $sessionId = $request->filled('pos_session_id') ? (int) $request->input('pos_session_id') : null;

        return Inertia::render('Pos/Cash/Index', [
            'movements' => $service->listCashMovements($sessionId, $perPage),
            'sessions' => $service->openSessionOptions(),
            'filters' => [
                'pos_session_id' => $sessionId,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function storeCash(StorePosCashMovementRequest $request, PosSessionService $service): RedirectResponse
    {
        $service->recordCashMovement($request->validated(), $request->user()->id);

        return back()->with('success', 'Cash movement recorded.');
    }
}
