<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Enums\ReviewStatus;
use Modules\Ecommerce\Http\Requests\BulkReviewModerationRequest;
use Modules\Ecommerce\Models\ProductReview;
use Modules\Ecommerce\Services\ProductReviewService;

class ProductReviewController extends Controller
{
    public function index(Request $request, ProductReviewService $service): Response
    {
        $queue = $request->string('queue', 'pending')->toString();
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        $status = match ($queue) {
            'approved' => ReviewStatus::Approved,
            'rejected' => ReviewStatus::Rejected,
            default => ReviewStatus::Pending,
        };

        return Inertia::render('Ecommerce/Reviews/Index', [
            'reviews' => $service->listPaginated(
                search: $search ?: null,
                status: $status,
                perPage: $perPage,
            ),
            'stats' => $service->moderationStats(),
            'filters' => [
                'queue' => in_array($queue, ['pending', 'approved', 'rejected'], true) ? $queue : 'pending',
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function approve(ProductReview $review, ProductReviewService $service): RedirectResponse
    {
        $service->transitionStatus($review, ReviewStatus::Approved, request()->user()->id);

        return back()->with('success', 'Review approved for storefront.');
    }

    public function reject(ProductReview $review, ProductReviewService $service): RedirectResponse
    {
        $service->transitionStatus($review, ReviewStatus::Rejected, request()->user()->id);

        return back()->with('success', 'Review rejected.');
    }

    public function bulkApprove(BulkReviewModerationRequest $request, ProductReviewService $service): RedirectResponse
    {
        $count = $service->bulkTransition(
            $request->validated('review_ids'),
            ReviewStatus::Approved,
            $request->user()->id,
        );

        return back()->with('success', "{$count} review(s) approved.");
    }

    public function bulkReject(BulkReviewModerationRequest $request, ProductReviewService $service): RedirectResponse
    {
        $count = $service->bulkTransition(
            $request->validated('review_ids'),
            ReviewStatus::Rejected,
            $request->user()->id,
        );

        return back()->with('success', "{$count} review(s) rejected.");
    }

    public function destroy(ProductReview $review, ProductReviewService $service): RedirectResponse
    {
        $service->delete($review);

        return back()->with('success', 'Review deleted.');
    }
}
