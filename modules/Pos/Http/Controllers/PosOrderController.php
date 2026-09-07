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
        $dateFrom = $this->nullableDate($request->string('date_from')->toString());
        $dateTo = $this->nullableDate($request->string('date_to')->toString());

        return Inertia::render('Pos/Orders/Index', [
            'orders' => $service->listPaginated($search ?: null, $status, $perPage, $dateFrom, $dateTo),
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
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

    private function nullableDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }
}
