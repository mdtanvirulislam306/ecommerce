<?php

namespace Modules\Crm\Http\Controllers;

use App\Core\Module\ModuleManager;
use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Crm\Enums\LeadStage;
use Modules\Crm\Http\Requests\MoveLeadStageRequest;
use Modules\Crm\Http\Requests\StoreLeadRequest;
use Modules\Crm\Http\Requests\UpdateLeadRequest;
use Modules\Crm\Models\Lead;
use Modules\Crm\Services\ActivityService;
use Modules\Crm\Services\CustomerService;
use Modules\Crm\Services\LeadService;
use Modules\Crm\Services\LeadSourceService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function index(Request $request, LeadService $service, LeadSourceService $sources, ActivityService $activities): InertiaResponse
    {
        $filters = $this->listFilters($request);
        $scope = ($filters['scope'] ?? 'all') === 'mine' ? 'mine' : 'all';
        $view = ($filters['pipeline_only'] ?? false) ? 'pipeline' : 'all';

        return Inertia::render('Crm/Leads/Index', [
            'leads' => $service->listPaginated($filters),
            'groups' => app(CustomerService::class)->groupOptions(),
            'sources' => $sources->options(),
            'assignees' => $service->assigneeOptions(),
            'stageOptions' => $view === 'pipeline'
                ? collect(LeadStage::pipelineStages())->map(fn (LeadStage $stage) => [
                    'value' => $stage->value,
                    'label' => $stage->label(),
                ])->all()
                : $service->stageOptions(),
            'moveStageOptions' => collect([...LeadStage::pipelineStages(), LeadStage::Lost])->map(fn (LeadStage $stage) => [
                'value' => $stage->value,
                'label' => $stage->label(),
            ])->all(),
            'stageCounts' => $view === 'pipeline' ? $service->pipelineStageCounts($filters) : [],
            'activityTypeOptions' => $activities->typeOptions(),
            'filters' => $this->visibleFilters($filters, $scope, $view),
            'scope' => $scope,
            'view' => $view,
            'listTitle' => $this->listTitle($scope, $view),
            'openCreate' => $request->boolean('create') || $request->routeIs('crm.leads.create'),
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(Request $request, LeadService $service, LeadSourceService $sources, ActivityService $activities): InertiaResponse
    {
        $request->merge(['create' => true]);

        return $this->index($request, $service, $sources, $activities);
    }

    public function my(Request $request): RedirectResponse
    {
        return redirect()->route('crm.leads.all', [
            ...$request->query(),
            'scope' => 'mine',
        ]);
    }

    public function pipeline(Request $request): RedirectResponse
    {
        return redirect()->route('crm.leads.all', [
            ...$request->query(),
            'view' => 'pipeline',
        ]);
    }

    public function export(Request $request, LeadService $service): StreamedResponse|Response
    {
        $validated = $request->validate([
            'format' => ['required', Rule::in(['csv', 'pdf', 'print'])],
        ]);

        $filters = $this->listFilters($request);
        $scope = ($filters['scope'] ?? 'all') === 'mine' ? 'mine' : 'all';
        $view = ($filters['pipeline_only'] ?? false) ? 'pipeline' : 'all';
        $rows = $service->listForExport($filters);
        $title = $this->listTitle($scope, $view);
        $generatedAt = Date::now()->timezone(config('app.timezone'))->format('Y-m-d H:i');

        if ($validated['format'] === 'csv') {
            return $this->csvDownload($rows, $title);
        }

        return response()->view('crm::leads-export', [
            'title' => $title,
            'rows' => $rows,
            'filters' => $this->exportFilterSummary($filters, $scope, $view),
            'generatedAt' => $generatedAt,
            'autoprint' => true,
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

    public function convert(Request $request, Lead $lead, LeadService $service): RedirectResponse
    {
        $customer = $service->convert($lead, $request->user()->id);
        $message = "Lead converted to customer {$customer->code}.";

        if ($request->boolean('create_order') && app(ModuleManager::class)->enabled('sales')) {
            return redirect()
                ->route('sales.orders.create', ['customer_id' => $customer->id])
                ->with('success', $message.' Continue with a sales order.');
        }

        return redirect()
            ->route('crm.customers.show', $customer)
            ->with('success', $message);
    }

    public function destroy(Lead $lead, LeadService $service): RedirectResponse
    {
        $service->delete($lead);

        return back()->with('success', 'Lead removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function listFilters(Request $request): array
    {
        [$dateFrom, $dateTo] = $this->dateRange($request);
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $converted = $request->string('converted')->toString();
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString();
        $scope = $request->string('scope')->toString();
        $view = $request->string('view')->toString();

        $isMine = $scope === 'mine'
            || $request->routeIs('crm.leads.my', 'crm.leads.my.export');
        $pipelineOnly = $view === 'pipeline'
            || $request->routeIs('crm.leads.pipeline', 'crm.leads.pipeline.export');

        $stageValue = $request->string('stage')->toString();
        if ($pipelineOnly) {
            $pipelineValues = array_map(fn (LeadStage $stage) => $stage->value, LeadStage::pipelineStages());
            $stage = in_array($stageValue, $pipelineValues, true)
                ? LeadStage::from($stageValue)
                : null;
        } else {
            $stage = $request->filled('stage')
                ? LeadStage::tryFrom($stageValue)
                : null;
        }

        return [
            'search' => $search ?: null,
            'stage' => $stage,
            'scope' => $isMine ? 'mine' : 'all',
            'assigned_to' => $isMine
                ? $request->user()->id
                : ($request->integer('assigned_to') ?: null),
            'source_id' => $request->integer('source_id') ?: null,
            'converted' => $pipelineOnly
                ? null
                : (in_array($converted, ['yes', 'no'], true) ? $converted : null),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'sort' => $sort ?: 'created_at',
            'direction' => $direction ?: 'desc',
            'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            'pipeline_only' => $pipelineOnly,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function visibleFilters(array $filters, string $scope, string $view): array
    {
        return [
            'search' => $filters['search'] ?? '',
            'stage' => $filters['stage']?->value,
            'source_id' => $filters['source_id'] ?? null,
            'assigned_to' => $scope === 'mine' ? null : ($filters['assigned_to'] ?? null),
            'converted' => $filters['converted'] ?? '',
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
            'sort' => $filters['sort'],
            'direction' => $filters['direction'],
            'per_page' => $filters['per_page'],
            'scope' => $scope,
            'view' => $view,
        ];
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function dateRange(Request $request): array
    {
        $from = $this->parseDate($request->string('date_from')->toString());
        $to = $this->parseDate($request->string('date_to')->toString());

        if ($from && $to && $from > $to) {
            return [$to, $from];
        }

        return [$from, $to];
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function csvDownload($rows, string $title): StreamedResponse
    {
        $filename = strtolower(str_replace(' ', '-', $title)).'-'.Date::now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Phone', 'Company', 'Stage', 'Source', 'Owner', 'Converted', 'Created']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['email'],
                    $row['phone'],
                    $row['company'],
                    $row['stage'],
                    $row['source'],
                    $row['owner'],
                    $row['converted'],
                    $row['created_at'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<string>
     */
    private function exportFilterSummary(array $filters, string $scope, string $view): array
    {
        $summary = [];

        if ($scope === 'mine') {
            $summary[] = 'Owner: Me';
        }

        if ($view === 'pipeline') {
            $summary[] = 'View: Pipeline';
        }

        if ($filters['search'] ?? null) {
            $summary[] = 'Search: '.$filters['search'];
        }

        if (($filters['stage'] ?? null) instanceof LeadStage) {
            $summary[] = 'Stage: '.$filters['stage']->label();
        }

        if ($filters['date_from'] ?? null) {
            $summary[] = 'From: '.$filters['date_from'];
        }

        if ($filters['date_to'] ?? null) {
            $summary[] = 'To: '.$filters['date_to'];
        }

        return $summary;
    }

    private function listTitle(string $scope, string $view): string
    {
        if ($view === 'pipeline' && $scope === 'mine') {
            return 'My Pipeline';
        }

        if ($view === 'pipeline') {
            return 'Lead Pipeline';
        }

        if ($scope === 'mine') {
            return 'My Leads';
        }

        return 'Leads';
    }
}
