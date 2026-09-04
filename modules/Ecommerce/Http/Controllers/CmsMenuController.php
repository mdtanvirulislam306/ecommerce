<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\StoreCmsMenuRequest;
use Modules\Ecommerce\Http\Requests\UpdateCmsMenuRequest;
use Modules\Ecommerce\Models\CmsMenu;
use Modules\Ecommerce\Services\CmsMenuService;

class CmsMenuController extends Controller
{
    public function index(Request $request, CmsMenuService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Ecommerce/Cms/Menus/Index', [
            'menus' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreCmsMenuRequest $request, CmsMenuService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Menu created.');
    }

    public function update(UpdateCmsMenuRequest $request, CmsMenu $cmsMenu, CmsMenuService $service): RedirectResponse
    {
        $service->update($cmsMenu, $request->validated());

        return back()->with('success', 'Menu updated.');
    }

    public function destroy(CmsMenu $cmsMenu, CmsMenuService $service): RedirectResponse
    {
        $service->delete($cmsMenu);

        return back()->with('success', 'Menu removed.');
    }
}
