<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Platform\Http\Requests\ProvisionTenantRequest;
use Modules\Platform\Http\Requests\UpdateTenantRequest;
use Modules\Platform\Services\ImpersonationService;
use Modules\Platform\Services\TenantProvisionService;

class TenantController extends Controller
{
    public function index(TenantProvisionService $tenants): Response
    {
        return Inertia::render('Platform/Tenants/Index', [
            'tenants' => $tenants->listTenants(),
        ]);
    }

    public function create(TenantProvisionService $tenants): Response
    {
        return Inertia::render('Platform/Tenants/Create', $tenants->formOptions());
    }

    public function store(ProvisionTenantRequest $request, TenantProvisionService $tenants): RedirectResponse
    {
        $tenant = $tenants->provision($request->validated());

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('success', 'Tenant provisioned successfully.');
    }

    public function show(Tenant $tenant, TenantProvisionService $tenants, ImpersonationService $impersonation): Response
    {
        $tenant->load(['domains', 'primaryDomain']);

        return Inertia::render('Platform/Tenants/Show', [
            'tenant' => $tenants->formatDetail($tenant),
            'team' => $tenants->team($tenant),
            'accessHistory' => $impersonation->history($tenant),
        ]);
    }

    public function edit(Tenant $tenant, TenantProvisionService $tenants): Response
    {
        $tenant->load(['domains', 'primaryDomain']);

        return Inertia::render('Platform/Tenants/Edit', [
            'tenant' => $tenants->formatDetail($tenant),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant, TenantProvisionService $tenants): RedirectResponse
    {
        $tenants->update($tenant, $request->validated());

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('success', 'Tenant updated.');
    }

    public function suspend(Tenant $tenant, TenantProvisionService $tenants): RedirectResponse
    {
        $tenants->suspend($tenant);

        return back()->with('success', 'Tenant suspended.');
    }

    public function activate(Tenant $tenant, TenantProvisionService $tenants): RedirectResponse
    {
        $tenants->activate($tenant);

        return back()->with('success', 'Tenant activated.');
    }
}
