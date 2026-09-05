<?php

namespace Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Pos\Http\Requests\CompletePosSaleRequest;
use Modules\Pos\Services\PosRegisterService;
use Modules\Pos\Services\PosSaleService;

class PosTerminalController extends Controller
{
    public function index(PosSaleService $sales, PosRegisterService $registers): Response
    {
        $register = $registers->ensureDefault();
        $session = $registers->currentOpenSession($register->id);

        return Inertia::render('Pos/Terminal/Index', [
            'register' => $registers->format($register->load('openSession')),
            'sessionOpen' => $session !== null,
            'products' => $sales->searchableProducts(limit: 40),
            'customers' => $sales->customerOptions(),
            'stats' => $sales->overviewStats(),
        ]);
    }

    public function search(Request $request, PosSaleService $sales): JsonResponse
    {
        $search = $request->string('q')->trim()->toString();
        $exact = $search !== '' ? $sales->findByBarcode($search) : null;

        return response()->json([
            'results' => $sales->searchableProducts($search ?: null),
            'exact' => $exact,
        ]);
    }

    public function complete(CompletePosSaleRequest $request, PosSaleService $sales): RedirectResponse
    {
        $order = $sales->completeSale($request->validated(), $request->user()->id);

        return redirect()
            ->route('pos.orders.show', $order)
            ->with('success', "Sale {$order->number} completed. Change: {$order->currency} {$order->change_due}");
    }
}
