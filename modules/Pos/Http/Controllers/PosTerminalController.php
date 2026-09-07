<?php

namespace Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crm\Services\CustomerService;
use Modules\Pos\Http\Requests\CompletePosSaleRequest;
use Modules\Pos\Http\Requests\StorePosTerminalCustomerRequest;
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
            'products' => $sales->searchableProducts(limit: 80),
            'categories' => $sales->categoryOptions(),
            'customers' => $sales->customerOptions(),
            'paymentMethods' => $sales->paymentMethodOptions(),
            'stats' => $sales->overviewStats(),
        ]);
    }

    public function search(Request $request, PosSaleService $sales): JsonResponse
    {
        $search = $request->string('q')->trim()->toString();
        $categoryId = $request->filled('category_id') ? $request->integer('category_id') : null;
        $exact = $search !== '' ? $sales->findByBarcode($search) : null;

        return response()->json([
            'results' => $sales->searchableProducts(
                search: $search !== '' ? $search : null,
                limit: 80,
                categoryId: $categoryId,
            ),
            'exact' => $exact,
        ]);
    }

    public function storeCustomer(
        StorePosTerminalCustomerRequest $request,
        CustomerService $customers,
    ): JsonResponse {
        $customer = $customers->create($request->validated(), $request->user()->id);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company' => $customer->company,
                'customer_group_id' => $customer->customer_group_id,
            ],
        ], 201);
    }

    public function complete(CompletePosSaleRequest $request, PosSaleService $sales): RedirectResponse
    {
        $order = $sales->completeSale($request->validated(), $request->user()->id);

        return redirect()
            ->route('pos.terminal')
            ->with('success', "Sale {$order->number} completed.")
            ->with('receipt', $sales->formatDetail($order));
    }
}
