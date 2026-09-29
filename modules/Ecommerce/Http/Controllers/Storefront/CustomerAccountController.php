<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\UpdateCustomerPasswordRequest;
use Modules\Ecommerce\Http\Requests\UpdateCustomerProfileRequest;
use Modules\Ecommerce\Services\CustomerAccountService;

class CustomerAccountController extends Controller
{
    public function index(Request $request, CustomerAccountService $accounts): Response
    {
        $account = $request->user('customer');

        return Inertia::render('Ecommerce/Shop/Account/Index', [
            'profile' => $accounts->profile($account),
            'stats' => $accounts->stats($account),
            'orders' => $accounts->orders($account),
        ]);
    }

    public function updateProfile(UpdateCustomerProfileRequest $request, CustomerAccountService $accounts): RedirectResponse
    {
        $accounts->updateProfile($request->user('customer'), $request->validated());

        return back()->with('success', 'Your details have been saved.');
    }

    public function updatePassword(UpdateCustomerPasswordRequest $request): RedirectResponse
    {
        $request->user('customer')->update(['password' => $request->validated('password')]);

        return back()->with('success', 'Your password has been changed.');
    }
}
