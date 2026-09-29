<?php

namespace Modules\Billing\Http\Controllers;

use App\Core\Module\ModuleManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Http\Requests\UpdatePlanRequest;
use Modules\Billing\Models\Plan;
use Modules\Billing\Services\PlanService;

class PlanController extends Controller
{
    public function index(PlanService $plans, ModuleManager $modules): Response
    {
        $subscription = $plans->activeSubscription();

        return Inertia::render('Billing/Plans/Index', [
            'plans' => $plans->listForAdmin($modules),
            'modules' => $modules->catalogForAdmin(),
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'plan_name' => $subscription->plan?->name,
                'plan_code' => $subscription->plan?->code,
                'status' => $subscription->status->value,
                'ends_at' => $subscription->ends_at?->toDateString(),
                'payment_note' => $subscription->payment_note,
            ] : null,
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan, PlanService $plans): RedirectResponse
    {
        $plans->update($plan, $request->validated());

        return back()->with('success', 'Plan updated.');
    }

    public function assign(Plan $plan, PlanService $plans): RedirectResponse
    {
        $plans->assignPlan($plan);

        return back()->with('success', "Shop switched to {$plan->name}.");
    }
}
