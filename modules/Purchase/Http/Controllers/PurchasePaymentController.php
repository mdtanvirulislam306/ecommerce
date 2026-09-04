<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Purchase\Http\Requests\StorePurchasePaymentRequest;
use Modules\Purchase\Http\Requests\UpdatePurchasePaymentRequest;
use Modules\Purchase\Models\PurchasePayment;
use Modules\Purchase\Services\PurchasePaymentService;

class PurchasePaymentController extends Controller
{
    public function index(Request $request, PurchasePaymentService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Purchase/Payments/Index', [
            'payments' => $service->listPaginated($search ?: null, $perPage),
            'orders' => $service->payableOrders(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StorePurchasePaymentRequest $request, PurchasePaymentService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user()->id);

        return back()->with('success', 'Supplier payment recorded.');
    }

    public function update(UpdatePurchasePaymentRequest $request, PurchasePayment $payment, PurchasePaymentService $service): RedirectResponse
    {
        $service->update($payment, $request->validated());

        return back()->with('success', 'Payment updated.');
    }

    public function destroy(PurchasePayment $payment, PurchasePaymentService $service): RedirectResponse
    {
        $service->delete($payment);

        return back()->with('success', 'Payment removed.');
    }
}
