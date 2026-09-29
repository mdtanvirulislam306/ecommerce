<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Enums\StaffStatus;
use Modules\Settings\Services\StaffService;
use Modules\Settings\Services\UserListService;

class UserListController extends Controller
{
    public function index(Request $request, UserListService $users, StaffService $staff): Response
    {
        $status = StaffStatus::tryFrom($request->string('status')->toString());

        return Inertia::render('Settings/Users/Index', [
            'users' => $users->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
                $status,
                $request->user(),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $status?->value,
                'per_page' => $request->integer('per_page', 25),
            ],
            'summary' => $staff->summary(),
            'roleOptions' => $users->roleOptions(),
            'invitationValidDays' => StaffService::INVITATION_VALID_DAYS,
        ]);
    }
}
