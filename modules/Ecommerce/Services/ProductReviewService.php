<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Enums\ReviewStatus;
use Modules\Ecommerce\Models\ProductReview;

class ProductReviewService extends Service
{
    /**
     * @return array{pending: int, approved: int, rejected: int}
     */
    public function moderationStats(): array
    {
        return [
            'pending' => ProductReview::query()->where('status', ReviewStatus::Pending)->count(),
            'approved' => ProductReview::query()->where('status', ReviewStatus::Approved)->count(),
            'rejected' => ProductReview::query()->where('status', ReviewStatus::Rejected)->count(),
        ];
    }

    public function listPaginated(
        ?string $search = null,
        ?ReviewStatus $status = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $query = ProductReview::query()
            ->join('products', 'product_reviews.product_id', '=', 'products.id')
            ->select([
                'product_reviews.*',
                'products.name as product_name',
                'products.sku as product_sku',
            ])
            ->orderByDesc('product_reviews.created_at');

        if ($status) {
            $query->where('product_reviews.status', $status->value);
        }

        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('product_reviews.author_name', 'like', "%{$search}%")
                    ->orWhere('product_reviews.title', 'like', "%{$search}%")
                    ->orWhere('product_reviews.body', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ProductReview $review) => $this->formatForList($review));
    }

    public function transitionStatus(ProductReview $review, ReviewStatus $to, int $moderatorId): ProductReview
    {
        if (! $review->status->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => "Cannot change review from {$review->status->label()} to {$to->label()}.",
            ]);
        }

        return DB::transaction(function () use ($review, $to, $moderatorId) {
            $review->status = $to;
            $review->moderated_by = $moderatorId;
            $review->approved_at = $to === ReviewStatus::Approved ? now() : null;
            $review->save();

            return $review;
        });
    }

    public function bulkTransition(array $reviewIds, ReviewStatus $to, int $moderatorId): int
    {
        $count = 0;

        foreach ($reviewIds as $id) {
            $review = ProductReview::query()->find($id);

            if ($review === null) {
                continue;
            }

            try {
                $this->transitionStatus($review, $to, $moderatorId);
                $count++;
            } catch (ValidationException) {
                //
            }
        }

        return $count;
    }

    public function delete(ProductReview $review): void
    {
        $review->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatForList(ProductReview $review): array
    {
        return [
            'id' => $review->id,
            'product_id' => $review->product_id,
            'product_name' => $review->product_name,
            'product_sku' => $review->product_sku,
            'author_name' => $review->author_name,
            'author_email' => $review->author_email,
            'rating' => $review->rating,
            'title' => $review->title,
            'body' => $review->body,
            'status' => $review->status->value,
            'status_label' => $review->status->label(),
            'approved_at' => $review->approved_at?->toIso8601String(),
            'created_at' => $review->created_at?->toIso8601String(),
        ];
    }
}
