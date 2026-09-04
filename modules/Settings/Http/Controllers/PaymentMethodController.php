<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Http\Requests\StorePaymentMethodRequest;
use Modules\Settings\Http\Requests\UpdatePaymentMethodRequest;
use Modules\Settings\Models\PaymentMethod;
use Modules\Settings\Services\PaymentMethodService;

class PaymentMethodController extends Controller
{
    public function index(Request $request, PaymentMethodService $svc): Response
    {
        return Inertia::render('Settings/PaymentMethods/Index', [
            'methods' => $svc->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'options' => method_exists($svc, 'formOptions') ? $svc->formOptions() : [],
        ]);
    }

    public function store(StorePaymentMethodRequest $request, PaymentMethodService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod, PaymentMethodService $svc): RedirectResponse
    {
        $svc->update($paymentMethod, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(PaymentMethod $paymentMethod, PaymentMethodService $svc): RedirectResponse
    {
        $svc->delete($paymentMethod);

        return back()->with('success', 'Deleted.');
    }
}
