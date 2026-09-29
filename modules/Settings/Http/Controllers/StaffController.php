<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Settings\Http\Requests\InviteStaffRequest;
use Modules\Settings\Services\StaffService;

class StaffController extends Controller
{
    public function __construct(private readonly StaffService $staff) {}

    public function store(InviteStaffRequest $request): RedirectResponse
    {
        $member = $this->staff->invite($request->validated(), $request->user());

        return back()->with('success', "Invitation sent to {$member->email}.");
    }

    public function resendInvitation(Request $request, int $user): RedirectResponse
    {
        $member = $this->staff->findManageable($user, $request->user());

        abort_unless($member->hasPendingInvitation() && ! $member->isDeactivated(), 422, 'This person has already joined.');

        $this->staff->resendInvitation($member, $request->user());

        return back()->with('success', "A fresh invitation is on its way to {$member->email}.");
    }

    public function deactivate(Request $request, int $user): RedirectResponse
    {
        $member = $this->staff->findManageable($user, $request->user());

        $this->staff->deactivate($member);

        return back()->with('success', "{$member->name} can no longer sign in.");
    }

    public function reactivate(Request $request, int $user): RedirectResponse
    {
        $member = $this->staff->findManageable($user, $request->user());

        $this->staff->reactivate($member);

        return back()->with('success', "{$member->name} can sign in again.");
    }

    /**
     * Only unanswered invitations are removed; people who joined are deactivated instead, so their history stays attributed.
     */
    public function destroy(Request $request, int $user): RedirectResponse
    {
        $member = $this->staff->findManageable($user, $request->user());

        abort_unless($member->hasPendingInvitation(), 422, 'Deactivate team members who have already joined.');

        $this->staff->cancelInvitation($member);

        return back()->with('success', "Invitation for {$member->email} cancelled.");
    }
}
