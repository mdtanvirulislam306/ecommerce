<?php

namespace Modules\Workflow\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Response;
use Modules\Workflow\Services\AutomationRuleService;

class BusinessRulesController extends AutomationRuleController
{
    public function index(Request $request, AutomationRuleService $svc): Response
    {
        return parent::index($request, $svc);
    }
}
