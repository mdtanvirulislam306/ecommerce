<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StoreLoyaltyPointRequest;
use Modules\Commerce\Http\Requests\UpdateLoyaltyPointRequest;
use Modules\Commerce\Models\LoyaltyPoint;
use Modules\Commerce\Services\LoyaltyPointService;

class LoyaltyPointController extends Controller
{
    public function index(Request $request, LoyaltyPointService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/Loyalty/Points', [
            'points' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreLoyaltyPointRequest $request, LoyaltyPointService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Loyalty account created.');
    }

    public function update(UpdateLoyaltyPointRequest $request, LoyaltyPoint $loyaltyPoint, LoyaltyPointService $service): RedirectResponse
    {
        $service->update($loyaltyPoint, $request->validated());

        return back()->with('success', 'Loyalty account updated.');
    }

    public function destroy(LoyaltyPoint $loyaltyPoint, LoyaltyPointService $service): RedirectResponse
    {
        $service->delete($loyaltyPoint);

        return back()->with('success', 'Loyalty account removed.');
    }
}
