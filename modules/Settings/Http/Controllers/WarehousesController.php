<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class WarehousesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Warehouses/Index');
    }
}
