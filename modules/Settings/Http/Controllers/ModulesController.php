<?php

namespace Modules\Settings\Http\Controllers;

use App\Core\Module\ModuleManager;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ModulesController extends Controller
{
    public function index(ModuleManager $modules): Response
    {
        return Inertia::render('Settings/Modules/Index', [
            'modules' => $modules->catalogForAdmin(),
        ]);
    }
}
