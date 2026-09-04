<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StoreReferralRequest;
use Modules\Commerce\Http\Requests\UpdateReferralRequest;
use Modules\Commerce\Models\Referral;
use Modules\Commerce\Services\ReferralService;

class ReferralController extends Controller
{
    public function index(Request $request, ReferralService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Commerce/Referrals/Index', [
            'referrals' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreReferralRequest $request, ReferralService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Referral created.');
    }

    public function update(UpdateReferralRequest $request, Referral $referral, ReferralService $service): RedirectResponse
    {
        $service->update($referral, $request->validated());

        return back()->with('success', 'Referral updated.');
    }

    public function destroy(Referral $referral, ReferralService $service): RedirectResponse
    {
        $service->delete($referral);

        return back()->with('success', 'Referral removed.');
    }
}
