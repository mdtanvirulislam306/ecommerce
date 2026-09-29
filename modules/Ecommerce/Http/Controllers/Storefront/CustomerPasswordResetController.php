<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Models\CustomerAccount;

class CustomerPasswordResetController extends Controller
{
    private const BROKER = 'customer_accounts';

    public function create(): Response
    {
        return Inertia::render('Ecommerce/Shop/Account/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * The reply is the same whether or not the email has an account, so the form can't be used to probe for customers.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::broker(self::BROKER)->sendResetLink(['email' => strtolower(trim($request->string('email')))]);

        return back()->with('status', 'If an account uses that email, a reset link is on its way. Check your inbox.');
    }

    public function edit(Request $request, string $token): Response
    {
        return Inertia::render('Ecommerce/Shop/Account/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker(self::BROKER)->reset(
            [...$request->only('password', 'password_confirmation', 'token'), 'email' => strtolower(trim($request->string('email')))],
            function (CustomerAccount $account, string $password) {
                $account->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($account));
            },
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => 'This reset link is invalid or has expired. Please request a new one.',
            ]);
        }

        return redirect()->route('shop.account.login')->with('status', 'Your password has been changed. Sign in with your new password.');
    }
}
