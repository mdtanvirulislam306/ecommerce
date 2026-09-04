<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StoreCourierRequest;
use Modules\Commerce\Http\Requests\UpdateCourierRequest;
use Modules\Commerce\Models\Courier;
use Modules\Commerce\Services\CourierService;

class CourierController extends Controller
{
    public function index(Request $request, CourierService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/Couriers/Index', [
            'couriers' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreCourierRequest $request, CourierService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Courier created.');
    }

    public function update(UpdateCourierRequest $request, Courier $courier, CourierService $service): RedirectResponse
    {
        $service->update($courier, $request->validated());

        return back()->with('success', 'Courier updated.');
    }

    public function destroy(Courier $courier, CourierService $service): RedirectResponse
    {
        $service->delete($courier);

        return back()->with('success', 'Courier removed.');
    }
}
