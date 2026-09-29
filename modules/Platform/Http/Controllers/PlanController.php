<?php

namespace Modules\Platform\Http\Controllers;

use App\Core\Module\ModuleManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Models\Plan;
use Modules\Billing\Services\PlanService;
use Modules\Platform\Http\Requests\StorePlanRequest;
use Modules\Platform\Http\Requests\UpdatePlanRequest;

class PlanController extends Controller
{
    public function index(PlanService $plans, ModuleManager $modules): Response
    {
        return Inertia::render('Platform/Plans/Index', [
            'plans' => $plans->listForPlatform(),
            'modules' => $this->moduleOptions($modules),
        ]);
    }

    public function create(ModuleManager $modules): Response
    {
        return Inertia::render('Platform/Plans/Create', [
            'modules' => $this->moduleOptions($modules),
        ]);
    }

    public function store(StorePlanRequest $request, PlanService $plans): RedirectResponse
    {
        $plan = $plans->create($request->validated());

        return redirect()
            ->route('platform.plans.index')
            ->with('success', "{$plan->name} plan created.");
    }

    public function edit(Plan $plan, PlanService $plans, ModuleManager $modules): Response
    {
        return Inertia::render('Platform/Plans/Edit', [
            'plan' => collect($plans->listForPlatform())->firstWhere('id', $plan->id),
            'modules' => $this->moduleOptions($modules),
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan, PlanService $plans): RedirectResponse
    {
        $plans->update($plan, $request->validated());

        if ($request->boolean('is_default') && ! $plan->is_default) {
            $plans->makeDefault($plan);
        }

        return redirect()
            ->route('platform.plans.index')
            ->with('success', "{$plan->name} plan saved.");
    }

    public function makeDefault(Plan $plan, PlanService $plans): RedirectResponse
    {
        $plans->makeDefault($plan);

        return back()->with('success', "New shops now start on {$plan->name}.");
    }

    public function destroy(Plan $plan, PlanService $plans): RedirectResponse
    {
        $plans->delete($plan);

        return redirect()
            ->route('platform.plans.index')
            ->with('success', "{$plan->name} plan deleted.");
    }

    /**
     * @return list<array{code: string, name: string, description: string, is_core: bool}>
     */
    private function moduleOptions(ModuleManager $modules): array
    {
        return collect($modules->all())
            ->map(fn ($module) => [
                'code' => $module->code,
                'name' => $module->name,
                'description' => $module->description,
                'is_core' => $module->isCore,
            ])
            ->sortBy([['is_core', 'desc'], ['name', 'asc']])
            ->values()
            ->all();
    }
}
