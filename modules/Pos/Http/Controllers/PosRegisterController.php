<?php

namespace Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Pos\Http\Requests\ClosePosSessionRequest;
use Modules\Pos\Http\Requests\OpenPosSessionRequest;
use Modules\Pos\Http\Requests\StorePosRegisterRequest;
use Modules\Pos\Http\Requests\UpdatePosRegisterRequest;
use Modules\Pos\Models\PosRegister;
use Modules\Pos\Models\PosSession;
use Modules\Pos\Services\PosRegisterService;

class PosRegisterController extends Controller
{
    public function index(PosRegisterService $service): Response
    {
        return Inertia::render('Pos/Registers/Index', [
            'registers' => $service->listAll(),
            'warehouses' => DB::table('warehouses')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'code'])
                ->map(fn ($row) => (array) $row)
                ->all(),
        ]);
    }

    public function store(StorePosRegisterRequest $request, PosRegisterService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Register created.');
    }

    public function update(UpdatePosRegisterRequest $request, PosRegister $posRegister, PosRegisterService $service): RedirectResponse
    {
        $service->update($posRegister, $request->validated());

        return back()->with('success', 'Register updated.');
    }

    public function openSession(OpenPosSessionRequest $request, PosRegister $posRegister, PosRegisterService $service): RedirectResponse
    {
        $service->openSession(
            $posRegister,
            (float) ($request->validated('opening_cash') ?? 0),
            $request->user()->id,
        );

        return back()->with('success', 'Session opened.');
    }

    public function closeSession(ClosePosSessionRequest $request, PosSession $posSession, PosRegisterService $service): RedirectResponse
    {
        $service->closeSession(
            $posSession,
            (float) $request->validated('closing_cash'),
            $request->user()->id,
        );

        return back()->with('success', 'Session closed.');
    }
}
