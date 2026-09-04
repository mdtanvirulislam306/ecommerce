<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\StoreStoreDomainRequest;
use Modules\Ecommerce\Http\Requests\UpdateStoreDomainRequest;
use Modules\Ecommerce\Models\StoreDomain;
use Modules\Ecommerce\Services\StoreDomainService;

class StoreDomainController extends Controller
{
    public function index(Request $request, StoreDomainService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Ecommerce/Store/Domains/Index', [
            'domains' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreStoreDomainRequest $request, StoreDomainService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Domain added.');
    }

    public function update(UpdateStoreDomainRequest $request, StoreDomain $storeDomain, StoreDomainService $service): RedirectResponse
    {
        $service->update($storeDomain, $request->validated());

        return back()->with('success', 'Domain updated.');
    }

    public function destroy(StoreDomain $storeDomain, StoreDomainService $service): RedirectResponse
    {
        $service->delete($storeDomain);

        return back()->with('success', 'Domain removed.');
    }
}
