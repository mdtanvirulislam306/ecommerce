<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Sales\Enums\PaymentMethod;
use Modules\Sales\Http\Requests\StorePaymentRequest;
use Modules\Sales\Services\InvoiceService;
use Modules\Sales\Services\PaymentService;

class PaymentController extends Controller
{
    public function index(Request $request, PaymentService $service, InvoiceService $invoices): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Sales/Payments/Index', [
            'payments' => $service->listPaginated($search ?: null, $perPage),
            'openInvoices' => $invoices->openInvoicesForSelect(),
            'methods' => collect(PaymentMethod::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StorePaymentRequest $request, PaymentService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user()->id);

        return redirect()
            ->route('sales.payments.index')
            ->with('success', 'Payment recorded.');
    }
}
