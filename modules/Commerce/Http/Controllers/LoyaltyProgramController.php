<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Http\Requests\StoreLoyaltyProgramRequest;
use Modules\Commerce\Http\Requests\UpdateLoyaltyProgramRequest;
use Modules\Commerce\Models\LoyaltyProgram;
use Modules\Commerce\Services\LoyaltyProgramService;

class LoyaltyProgramController extends Controller
{
    public function index(LoyaltyProgramService $service): Response
    {
        return Inertia::render('Commerce/Loyalty/Program', [
            'program' => $service->get(),
        ]);
    }

    public function store(StoreLoyaltyProgramRequest $request, LoyaltyProgramService $service): RedirectResponse
    {
        if ($service->get() !== null) {
            return back()->withErrors(['name' => 'A loyalty program already exists. Edit it instead.']);
        }

        $service->create($request->validated());

        return back()->with('success', 'Loyalty program created.');
    }

    public function update(UpdateLoyaltyProgramRequest $request, LoyaltyProgram $loyaltyProgram, LoyaltyProgramService $service): RedirectResponse
    {
        $service->update($loyaltyProgram, $request->validated());

        return back()->with('success', 'Loyalty program updated.');
    }
}
