<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Http\Requests\StoreRoleRequest;
use Modules\Settings\Http\Requests\UpdateRoleRequest;
use Modules\Settings\Models\Role;
use Modules\Settings\Services\RoleService;

class RoleController extends Controller
{
    public function index(Request $request, RoleService $svc): Response
    {
        return Inertia::render('Settings/Roles/Index', [
            'roles' => $svc->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'options' => method_exists($svc, 'formOptions') ? $svc->formOptions() : [],
        ]);
    }

    public function store(StoreRoleRequest $request, RoleService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateRoleRequest $request, Role $role, RoleService $svc): RedirectResponse
    {
        $svc->update($role, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(Role $role, RoleService $svc): RedirectResponse
    {
        $svc->delete($role);

        return back()->with('success', 'Deleted.');
    }
}
