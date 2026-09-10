<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\StoreCmsPageRequest;
use Modules\Ecommerce\Http\Requests\UpdateCmsPageRequest;
use Modules\Ecommerce\Models\CmsPage;
use Modules\Ecommerce\Services\CmsPageService;
use Modules\Ecommerce\Services\PageBuilder\PageBuilderRegistry;
use Modules\Ecommerce\Services\StorefrontCatalogService;

class CmsPageBuilderController extends Controller
{
    public function show(
        PageBuilderRegistry $registry,
        StorefrontCatalogService $catalog,
        ?CmsPage $cmsPage = null,
    ): Response {
        return Inertia::render('Ecommerce/Cms/Pages/Builder', [
            'page' => $cmsPage ? [
                ...$cmsPage->toArray(),
                'blocks' => $registry->documentForEditor($cmsPage),
            ] : null,
            'widgetCatalog' => $registry->catalog(),
            'layouts' => $registry->layouts(),
            'emptyDocument' => $registry->emptyDocument(),
            'catalogPreview' => [
                'products' => $catalog->featuredProducts(8),
                'categories' => $catalog->activeCategories(12),
            ],
        ]);
    }

    public function store(StoreCmsPageRequest $request, CmsPageService $service): RedirectResponse
    {
        $page = $service->create($request->validated());

        return redirect()
            ->route('ecommerce.pages.builder.edit', $page)
            ->with('success', 'Page created.');
    }

    public function update(UpdateCmsPageRequest $request, CmsPage $cmsPage, CmsPageService $service): RedirectResponse
    {
        $service->update($cmsPage, $request->validated());

        return back()->with('success', 'Page saved.');
    }
}
