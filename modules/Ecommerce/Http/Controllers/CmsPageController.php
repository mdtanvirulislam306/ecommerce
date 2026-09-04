<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\StoreCmsPageRequest;
use Modules\Ecommerce\Http\Requests\UpdateCmsPageRequest;
use Modules\Ecommerce\Models\CmsPage;
use Modules\Ecommerce\Services\CmsPageService;

class CmsPageController extends Controller
{
    public function index(Request $request, CmsPageService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Ecommerce/Cms/Pages/Index', [
            'pages' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreCmsPageRequest $request, CmsPageService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Page created.');
    }

    public function update(UpdateCmsPageRequest $request, CmsPage $cmsPage, CmsPageService $service): RedirectResponse
    {
        $service->update($cmsPage, $request->validated());

        return back()->with('success', 'Page updated.');
    }

    public function destroy(CmsPage $cmsPage, CmsPageService $service): RedirectResponse
    {
        $service->delete($cmsPage);

        return back()->with('success', 'Page removed.');
    }
}
