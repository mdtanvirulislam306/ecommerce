<?php

namespace App\Http\Requests\Auth;

use App\Core\Tenant\TenantContext;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = (string) $this->string('email');
        $password = (string) $this->string('password');
        $tenants = app(TenantContext::class);

        $query = User::query()->where('email', $email);

        if ($tenants->isPlatformRequest()) {
            $query->where('is_platform_admin', true)->whereNull('tenant_id');
        } elseif ($tenants->id() !== null) {
            $query->where(function ($inner) use ($tenants): void {
                $inner->where('tenant_id', $tenants->id())
                    ->orWhere(function ($platform) {
                        $platform->where('is_platform_admin', true)->whereNull('tenant_id');
                    });
            });
        }

        $user = $query->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if ($user->isDeactivated()) {
            throw ValidationException::withMessages([
                'email' => 'Your access to this shop has been turned off. Ask the shop owner to restore it.',
            ]);
        }

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());

        $user->forceFill([
            'last_login_at' => now(),
            ...($user->hasPendingInvitation() ? ['invitation_accepted_at' => now(), 'invitation_token' => null] : []),
        ])->save();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
