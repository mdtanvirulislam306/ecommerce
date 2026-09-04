<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AreasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Areas/Index');
    }
}
