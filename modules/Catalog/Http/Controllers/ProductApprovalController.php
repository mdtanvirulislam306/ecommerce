<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Http\Requests\BulkProductApprovalRequest;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\ProductService;

class ProductApprovalController extends Controller
{
    public function index(Request $request, ProductService $service): Response
    {
        $queue = $request->string('queue', 'pending')->toString();
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        $status = $queue === 'approved'
            ? ProductStatus::Approved
            : ProductStatus::PendingReview;

        return Inertia::render('Catalog/Approval/Index', [
            'products' => $service->listPaginated(
                search: $search ?: null,
                status: $status,
                perPage: $perPage,
            ),
            'stats' => $service->approvalStats(),
            'filters' => [
                'queue' => $queue === 'approved' ? 'approved' : 'pending',
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function submitReview(Product $product, ProductService $service): RedirectResponse
    {
        $service->transitionStatus($product, ProductStatus::PendingReview);

        return back()->with('success', 'Product submitted for review.');
    }

    public function approve(Product $product, ProductService $service): RedirectResponse
    {
        $service->transitionStatus($product, ProductStatus::Approved);

        return back()->with('success', 'Product approved.');
    }

    public function reject(Product $product, ProductService $service): RedirectResponse
    {
        $service->transitionStatus($product, ProductStatus::Draft);

        return back()->with('success', 'Product returned to draft.');
    }

    public function activate(Product $product, ProductService $service): RedirectResponse
    {
        $service->transitionStatus($product, ProductStatus::Active);

        return back()->with('success', 'Product is now active.');
    }

    public function archive(Product $product, ProductService $service): RedirectResponse
    {
        $service->transitionStatus($product, ProductStatus::Archived);

        return back()->with('success', 'Product archived.');
    }

    public function bulkApprove(BulkProductApprovalRequest $request, ProductService $service): RedirectResponse
    {
        $count = $service->bulkTransition($request->validated('product_ids'), ProductStatus::Approved);

        return back()->with('success', "{$count} product(s) approved.");
    }

    public function bulkReject(BulkProductApprovalRequest $request, ProductService $service): RedirectResponse
    {
        $count = $service->bulkTransition($request->validated('product_ids'), ProductStatus::Draft);

        return back()->with('success', "{$count} product(s) returned to draft.");
    }

    public function bulkActivate(BulkProductApprovalRequest $request, ProductService $service): RedirectResponse
    {
        $count = $service->bulkTransition($request->validated('product_ids'), ProductStatus::Active);

        return back()->with('success', "{$count} product(s) activated.");
    }

    public function bulkSubmit(BulkProductApprovalRequest $request, ProductService $service): RedirectResponse
    {
        $count = $service->bulkTransition($request->validated('product_ids'), ProductStatus::PendingReview);

        return back()->with('success', "{$count} product(s) submitted for review.");
    }
}
