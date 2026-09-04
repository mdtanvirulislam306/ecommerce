<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModulePageController extends Controller
{
    private const MODULES = [
        'crm', 'products', 'inventory', 'purchase', 'sales', 'pos', 'ecommerce',
        'accounting', 'marketing', 'commerce',
    ];

    public function show(Request $request, string $module, ?string $page = null, ?string $sub = null): Response
    {
        abort_unless(in_array($module, self::MODULES, true), 404);

        $page = $page ?? 'overview';

        return Inertia::render('Admin/ModulePage', [
            'module' => $module,
            'page' => $page,
            'sub' => $sub,
            'title' => $this->resolveTitle($module, $page, $sub),
        ]);
    }

    private function resolveTitle(string $module, string $page, ?string $sub): string
    {
        $parts = array_map(
            fn (string $part) => str($part)->replace('-', ' ')->title()->toString(),
            array_filter([$page, $sub]),
        );

        if ($parts === []) {
            return str($module)->replace('-', ' ')->title()->toString();
        }

        return implode(' — ', $parts);
    }
}
