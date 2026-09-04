<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Http\Requests\StoreShippingMethodRequest;
use Modules\Settings\Http\Requests\UpdateShippingMethodRequest;
use Modules\Settings\Models\ShippingMethod;
use Modules\Settings\Services\ShippingMethodService;

class ShippingMethodController extends Controller
{
    public function index(Request $request, ShippingMethodService $svc): Response
    {
        return Inertia::render('Settings/ShippingMethods/Index', [
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

    public function store(StoreShippingMethodRequest $request, ShippingMethodService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateShippingMethodRequest $request, ShippingMethod $shippingMethod, ShippingMethodService $svc): RedirectResponse
    {
        $svc->update($shippingMethod, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(ShippingMethod $shippingMethod, ShippingMethodService $svc): RedirectResponse
    {
        $svc->delete($shippingMethod);

        return back()->with('success', 'Deleted.');
    }
}
