<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Http\Requests\StoreAuditLogRequest;
use Modules\Settings\Http\Requests\UpdateAuditLogRequest;
use Modules\Settings\Models\AuditLog;
use Modules\Settings\Services\AuditLogService;

class AuditLogController extends Controller
{
    public function index(Request $request, AuditLogService $svc): Response
    {
        return Inertia::render('Settings/AuditLogs/Index', [
            'logs' => $svc->listPaginated(
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

    public function store(StoreAuditLogRequest $request, AuditLogService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateAuditLogRequest $request, AuditLog $auditLog, AuditLogService $svc): RedirectResponse
    {
        $svc->update($auditLog, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(AuditLog $auditLog, AuditLogService $svc): RedirectResponse
    {
        $svc->delete($auditLog);

        return back()->with('success', 'Deleted.');
    }
}
