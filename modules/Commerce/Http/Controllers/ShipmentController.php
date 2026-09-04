<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StoreShipmentRequest;
use Modules\Commerce\Http\Requests\UpdateShipmentRequest;
use Modules\Commerce\Models\Shipment;
use Modules\Commerce\Services\CourierService;
use Modules\Commerce\Services\ShipmentService;

class ShipmentController extends Controller
{
    public function index(Request $request, ShipmentService $service, CourierService $courierService): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/Shipments/Index', [
            'shipments' => $service->listPaginated($search ?: null, $perPage),
            'couriers' => $courierService->activeCouriers(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function tracking(Request $request, ShipmentService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/Shipments/Tracking', [
            'shipments' => $service->listForTracking($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreShipmentRequest $request, ShipmentService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Shipment created.');
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment, ShipmentService $service): RedirectResponse
    {
        $service->update($shipment, $request->validated());

        return back()->with('success', 'Shipment updated.');
    }

    public function destroy(Shipment $shipment, ShipmentService $service): RedirectResponse
    {
        $service->delete($shipment);

        return back()->with('success', 'Shipment removed.');
    }
}
