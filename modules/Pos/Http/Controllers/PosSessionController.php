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
        $dateFrom = $this->nullableDate($request->string('date_from')->toString());
        $dateTo = $this->nullableDate($request->string('date_to')->toString());

        return Inertia::render('Pos/Sessions/Open', [
            'sessions' => $service->listOpen($search ?: null, $perPage, $dateFrom, $dateTo),
            'filters' => [
                'search' => $search,
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function history(Request $request, PosSessionService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $dateFrom = $this->nullableDate($request->string('date_from')->toString());
        $dateTo = $this->nullableDate($request->string('date_to')->toString());

        return Inertia::render('Pos/Sessions/History', [
            'sessions' => $service->listHistory($search ?: null, $perPage, $dateFrom, $dateTo),
            'filters' => [
                'search' => $search,
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function cash(Request $request, PosSessionService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $sessionId = $request->filled('pos_session_id') ? (int) $request->input('pos_session_id') : null;
        $type = $request->string('type')->toString();
        $dateFrom = $this->nullableDate($request->string('date_from')->toString());
        $dateTo = $this->nullableDate($request->string('date_to')->toString());

        return Inertia::render('Pos/Cash/Index', [
            'movements' => $service->listCashMovements($sessionId, $perPage, $type ?: null, $dateFrom, $dateTo),
            'sessions' => $service->openSessionOptions(),
            'filters' => [
                'pos_session_id' => $sessionId,
                'type' => in_array($type, ['in', 'out'], true) ? $type : '',
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
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

    private function nullableDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }
}
