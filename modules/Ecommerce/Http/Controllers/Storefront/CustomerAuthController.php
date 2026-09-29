<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\CustomerLoginRequest;
use Modules\Ecommerce\Http\Requests\CustomerRegisterRequest;
use Modules\Ecommerce\Services\CustomerAccountService;

class CustomerAuthController extends Controller
{
    /**
     * Kept apart from Laravel's "url.intended" so a staff redirect never leaks into the storefront.
     */
    public const INTENDED_KEY = 'shop.intended';

    /** Destinations a sign-in link may ask to return to. */
    private const RETURN_ROUTES = ['checkout' => 'shop.checkout'];

    public function createLogin(Request $request): Response
    {
        $this->rememberReturnTo($request);

        return Inertia::render('Ecommerce/Shop/Account/Login', [
            'status' => session('status'),
            'returningToCheckout' => $request->session()->get(self::INTENDED_KEY) === route('shop.checkout'),
        ]);
    }

    public function storeLogin(CustomerLoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $account = Auth::guard('customer')->user();
        $account->forceFill(['last_login_at' => now()])->save();

        return redirect($this->intendedUrl($request))->with('success', "Welcome back, {$account->name}!");
    }

    public function createRegister(Request $request): Response
    {
        $this->rememberReturnTo($request);

        return Inertia::render('Ecommerce/Shop/Account/Register', [
            'returningToCheckout' => $request->session()->get(self::INTENDED_KEY) === route('shop.checkout'),
        ]);
    }

    public function storeRegister(CustomerRegisterRequest $request, CustomerAccountService $accounts): RedirectResponse
    {
        $account = $accounts->register($request->validated());

        Auth::guard('customer')->login($account, remember: true);
        $request->session()->regenerate();

        return redirect($this->intendedUrl($request))->with('success', "Welcome, {$account->name}! Your account is ready.");
    }

    /**
     * Only the customer guard is cleared; a shop owner testing their own storefront stays signed in to the admin.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('shop.index')->with('success', 'You have been signed out.');
    }

    private function rememberReturnTo(Request $request): void
    {
        $routeName = self::RETURN_ROUTES[$request->query('redirect')] ?? null;

        if ($routeName !== null) {
            $request->session()->put(self::INTENDED_KEY, route($routeName));
        }
    }

    private function intendedUrl(Request $request): string
    {
        return $request->session()->pull(self::INTENDED_KEY, route('shop.account.index'));
    }
}
