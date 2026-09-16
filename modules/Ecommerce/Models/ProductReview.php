<?php

namespace Modules\Ecommerce\Models;

use App\Core\Support\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\Enums\ReviewStatus;

class ProductReview extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'product_id',
        'user_id',
        'author_name',
        'author_email',
        'rating',
        'title',
        'body',
        'status',
        'approved_at',
        'moderated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
