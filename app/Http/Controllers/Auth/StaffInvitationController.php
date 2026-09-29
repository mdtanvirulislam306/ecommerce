<?php

namespace App\Http\Controllers\Auth;

use App\Core\Tenant\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\StaffService;

class StaffInvitationController extends Controller
{
    public function __construct(private readonly StaffService $staff) {}

    /**
     * An expired or used link still renders the page, explaining what to do instead of showing a bare 404.
     */
    public function show(string $token, TenantContext $tenants): Response
    {
        $member = $this->staff->findByInvitationToken($token);

        return Inertia::render('Auth/AcceptInvitation', [
            'token' => $token,
            'shopName' => $tenants->get()?->name ?? config('app.name'),
            'invitation' => $member === null ? null : [
                'name' => $member->name,
                'email' => $member->email,
                'inviter' => $member->inviter?->name,
                'roles' => $member->roles()->orderBy('name')->pluck('name')->all(),
            ],
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $member = $this->staff->findByInvitationToken($token);

        if ($member === null) {
            throw ValidationException::withMessages([
                'password' => 'This invitation link is no longer valid. Ask the shop owner to send a new one.',
            ]);
        }

        $this->staff->acceptInvitation($member, $request->validated('name'), $request->validated('password'));

        Auth::guard('web')->login($member);
        $request->session()->regenerate();
        $member->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('dashboard')->with('success', "Welcome aboard, {$member->name}!");
    }
}
