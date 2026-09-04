<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Ecommerce\Http\Requests\BulkPublicationRequest;
use Modules\Ecommerce\Http\Requests\BulkStorefrontPresentationRequest;
use Modules\Ecommerce\Http\Requests\UpdateStorefrontPresentationRequest;
use Modules\Ecommerce\Services\OnlineProductService;

class OnlineProductController extends Controller
{
    public function index(Request $request, OnlineProductService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        $publication = $request->filled('publication')
            ? PublicationStatus::tryFrom($request->string('publication')->toString())
            : null;

        $lifecycle = $request->filled('lifecycle')
            ? $request->string('lifecycle')->toString()
            : null;

        $featured = $request->filled('featured')
            ? $request->boolean('featured')
            : null;

        $homepage = $request->filled('homepage')
            ? $request->boolean('homepage')
            : null;

        return Inertia::render('Ecommerce/OnlineProducts/Index', [
            'products' => $service->listPaginated(
                search: $search ?: null,
                publicationStatus: $publication,
                lifecycleStatus: $lifecycle,
                featuredOnly: $featured,
                homepageOnly: $homepage,
                perPage: $perPage,
            ),
            'stats' => $service->publicationStats(),
            'filters' => [
                'search' => $search,
                'publication' => $publication?->value,
                'lifecycle' => $lifecycle,
                'featured' => $featured,
                'homepage' => $homepage,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'publicationStatuses' => collect(PublicationStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function updatePresentation(
        int $productId,
        UpdateStorefrontPresentationRequest $request,
        OnlineProductService $service,
    ): RedirectResponse {
        $service->updatePresentation($productId, $request->validated());

        return back()->with('success', 'Storefront presentation updated.');
    }

    public function bulkPresentation(
        BulkStorefrontPresentationRequest $request,
        OnlineProductService $service,
    ): RedirectResponse {
        $data = array_filter(
            $request->only(['is_featured', 'show_on_homepage']),
            fn ($value) => $value !== null,
        );

        $count = $service->bulkUpdatePresentation($request->validated('product_ids'), $data);

        return back()->with('success', "{$count} product(s) updated.");
    }

    public function publish(int $productId, OnlineProductService $service): RedirectResponse
    {
        $service->transitionPublication($productId, PublicationStatus::Published);

        return back()->with('success', 'Product published to storefront.');
    }

    public function unpublish(int $productId, OnlineProductService $service): RedirectResponse
    {
        $service->transitionPublication($productId, PublicationStatus::Unpublished);

        return back()->with('success', 'Product unpublished from storefront.');
    }

    public function bulkPublish(BulkPublicationRequest $request, OnlineProductService $service): RedirectResponse
    {
        $count = $service->bulkTransition($request->validated('product_ids'), PublicationStatus::Published);

        return back()->with('success', "{$count} product(s) published.");
    }

    public function bulkUnpublish(BulkPublicationRequest $request, OnlineProductService $service): RedirectResponse
    {
        $count = $service->bulkTransition($request->validated('product_ids'), PublicationStatus::Unpublished);

        return back()->with('success', "{$count} product(s) unpublished.");
    }
}
