<?php

namespace Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Marketing\Http\Requests\StoreLoyaltySettingRequest;
use Modules\Marketing\Http\Requests\UpdateLoyaltySettingRequest;
use Modules\Marketing\Models\LoyaltySetting;
use Modules\Marketing\Services\LoyaltySettingService;

class MarketingLoyaltyController extends Controller
{
    public function index(Request $request, LoyaltySettingService $loyalty): Response
    {
        return Inertia::render('Marketing/Loyalty/Index', [
            'programs' => $loyalty->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
        ]);
    }

    public function store(StoreLoyaltySettingRequest $request, LoyaltySettingService $loyalty): RedirectResponse
    {
        $loyalty->create($request->validated());

        return back()->with('success', 'Loyalty program created.');
    }

    public function update(UpdateLoyaltySettingRequest $request, LoyaltySetting $loyaltySetting, LoyaltySettingService $loyalty): RedirectResponse
    {
        $loyalty->update($loyaltySetting, $request->validated());

        return back()->with('success', 'Loyalty program updated.');
    }

    public function destroy(LoyaltySetting $loyaltySetting, LoyaltySettingService $loyalty): RedirectResponse
    {
        $loyalty->delete($loyaltySetting);

        return back()->with('success', 'Loyalty program deleted.');
    }
}
