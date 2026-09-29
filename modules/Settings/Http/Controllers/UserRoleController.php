<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Settings\Http\Requests\AssignUserRolesRequest;
use Modules\Settings\Services\StaffService;
use Modules\Settings\Services\UserListService;

class UserRoleController extends Controller
{
    public function update(AssignUserRolesRequest $request, int $user, UserListService $users, StaffService $staffService): RedirectResponse
    {
        $staff = $staffService->findManageable($user, $request->user());

        $users->syncRoles($staff, $request->validated('role_ids'));

        return back()->with('success', "Roles updated for {$staff->name}.");
    }
}
