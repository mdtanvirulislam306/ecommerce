<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\SubmitCmsPageFormRequest;
use Modules\Ecommerce\Models\CmsPage;
use Modules\Ecommerce\Services\CmsFormSubmissionService;
use Modules\Ecommerce\Services\PageBuilder\PageBuilderRegistry;
use Modules\Ecommerce\Services\StorefrontCatalogService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CmsPageController extends Controller
{
    public function show(
        string $slug,
        PageBuilderRegistry $registry,
        StorefrontCatalogService $catalog,
    ): Response {
        $page = CmsPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if ($page === null) {
            throw new NotFoundHttpException;
        }

        return $this->render($page, $registry, $catalog);
    }

    public function preview(
        string $slug,
        PageBuilderRegistry $registry,
        StorefrontCatalogService $catalog,
    ): Response {
        $page = CmsPage::query()->where('slug', $slug)->first();

        if ($page === null) {
            throw new NotFoundHttpException;
        }

        return $this->render($page, $registry, $catalog);
    }

    public function submitForm(
        string $slug,
        SubmitCmsPageFormRequest $request,
        CmsFormSubmissionService $service,
    ): RedirectResponse {
        $page = CmsPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if ($page === null) {
            throw new NotFoundHttpException;
        }

        $service->submit(
            $page,
            $request->safe()->only(['type', 'widget_id', 'name', 'email', 'message']),
            $request->ip(),
        );

        $message = $request->string('type')->toString() === 'newsletter'
            ? 'Thanks for subscribing.'
            : 'Thanks, we received your message.';

        return back()->with('success', $message);
    }

    private function render(
        CmsPage $page,
        PageBuilderRegistry $registry,
        StorefrontCatalogService $catalog,
    ): Response {
        $document = $registry->documentForEditor($page);

        return Inertia::render('Ecommerce/Shop/CmsPage', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'seo_title' => $page->seo_title ?: $page->title,
                'seo_description' => $page->seo_description,
                'is_published' => $page->is_published,
                'blocks' => $registry->hydrate($document, $catalog),
            ],
        ]);
    }
}
