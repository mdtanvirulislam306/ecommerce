<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crm\Enums\LeadStage;
use Modules\Crm\Http\Requests\MoveLeadStageRequest;
use Modules\Crm\Http\Requests\StoreLeadRequest;
use Modules\Crm\Http\Requests\UpdateLeadRequest;
use Modules\Crm\Models\Lead;
use Modules\Crm\Services\CustomerService;
use Modules\Crm\Services\LeadService;
use Modules\Crm\Services\LeadSourceService;

class LeadController extends Controller
{
    public function index(Request $request, LeadService $service, LeadSourceService $sources): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $stage = $request->filled('stage')
            ? LeadStage::tryFrom($request->string('stage')->toString())
            : null;
        $assignedTo = $request->routeIs('crm.leads.my') ? $request->user()->id : null;

        return Inertia::render('Crm/Leads/Index', [
            'leads' => $service->listPaginated($search ?: null, $stage, $assignedTo, $perPage),
            'groups' => app(CustomerService::class)->groupOptions(),
            'sources' => $sources->options(),
            'stageOptions' => $service->stageOptions(),
            'filters' => [
                'search' => $search,
                'stage' => $stage?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'listTitle' => $request->routeIs('crm.leads.my') ? 'My Leads' : 'All Leads',
            'openCreate' => $request->boolean('create') || $request->routeIs('crm.leads.create'),
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(Request $request, LeadService $service, LeadSourceService $sources): Response
    {
        $request->merge(['create' => true]);

        return $this->index($request, $service, $sources);
    }

    public function pipeline(LeadService $service, CustomerService $customers): Response
    {
        return Inertia::render('Crm/Leads/Pipeline', [
            'board' => $service->pipelineBoard(),
            'stageOptions' => collect(LeadStage::pipelineStages())->map(fn (LeadStage $stage) => [
                'value' => $stage->value,
                'label' => $stage->label(),
            ])->all(),
            'groups' => $customers->groupOptions(),
        ]);
    }

    public function store(StoreLeadRequest $request, LeadService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user()->id);

        return back()->with('success', 'Lead created.');
    }

    public function update(UpdateLeadRequest $request, Lead $lead, LeadService $service): RedirectResponse
    {
        $service->update($lead, $request->validated());

        return back()->with('success', 'Lead updated.');
    }

    public function moveStage(MoveLeadStageRequest $request, Lead $lead, LeadService $service): RedirectResponse
    {
        $stage = LeadStage::from($request->validated('stage'));
        $service->moveStage($lead, $stage);

        return back()->with('success', 'Lead stage updated.');
    }

    public function convert(Lead $lead, LeadService $service): RedirectResponse
    {
        $customer = $service->convert($lead, request()->user()->id);

        return redirect()
            ->route('crm.customers.all')
            ->with('success', "Lead converted to customer {$customer->code}.");
    }

    public function destroy(Lead $lead, LeadService $service): RedirectResponse
    {
        $service->delete($lead);

        return back()->with('success', 'Lead removed.');
    }
}
