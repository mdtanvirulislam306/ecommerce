<?php

namespace Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Pos\Enums\PosOrderStatus;
use Modules\Pos\Models\PosOrder;
use Modules\Pos\Services\PosSaleService;

class PosOrderController extends Controller
{
    public function index(Request $request, PosSaleService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $status = $request->filled('status')
            ? PosOrderStatus::tryFrom($request->string('status')->toString())
            : null;

        return Inertia::render('Pos/Orders/Index', [
            'orders' => $service->listPaginated($search ?: null, $status, $perPage),
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'statusOptions' => collect(PosOrderStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function show(PosOrder $posOrder, PosSaleService $service): Response
    {
        return Inertia::render('Pos/Orders/Show', [
            'order' => $service->formatDetail($posOrder),
        ]);
    }

    public function cancel(PosOrder $posOrder, PosSaleService $service): RedirectResponse
    {
        $service->cancel($posOrder, request()->user()->id);

        return back()->with('success', 'POS sale cancelled and stock restocked.');
    }
}
