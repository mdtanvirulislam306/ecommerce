<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Http\Requests\StoreCurrencyRequest;
use Modules\Settings\Http\Requests\UpdateCurrencyRequest;
use Modules\Settings\Models\Currency;
use Modules\Settings\Services\CurrencyService;

class CurrencyController extends Controller
{
    public function index(Request $request, CurrencyService $svc): Response
    {
        return Inertia::render('Settings/Currency/Index', [
            'currencies' => $svc->listPaginated(
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

    public function store(StoreCurrencyRequest $request, CurrencyService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency, CurrencyService $svc): RedirectResponse
    {
        $svc->update($currency, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(Currency $currency, CurrencyService $svc): RedirectResponse
    {
        $svc->delete($currency);

        return back()->with('success', 'Deleted.');
    }
}
