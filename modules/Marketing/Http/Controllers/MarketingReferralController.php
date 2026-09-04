<?php

namespace Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Marketing\Http\Requests\StoreReferralRequest;
use Modules\Marketing\Http\Requests\UpdateReferralRequest;
use Modules\Marketing\Models\Referral;
use Modules\Marketing\Services\ReferralService;

class MarketingReferralController extends Controller
{
    public function index(Request $request, ReferralService $referrals): Response
    {
        return Inertia::render('Marketing/Referrals/Index', [
            'referrals' => $referrals->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'statuses' => $referrals->statusOptions(),
        ]);
    }

    public function store(StoreReferralRequest $request, ReferralService $referrals): RedirectResponse
    {
        $referrals->create($request->validated());

        return back()->with('success', 'Referral created.');
    }

    public function update(UpdateReferralRequest $request, Referral $referral, ReferralService $referrals): RedirectResponse
    {
        $referrals->update($referral, $request->validated());

        return back()->with('success', 'Referral updated.');
    }

    public function destroy(Referral $referral, ReferralService $referrals): RedirectResponse
    {
        $referrals->delete($referral);

        return back()->with('success', 'Referral deleted.');
    }
}
