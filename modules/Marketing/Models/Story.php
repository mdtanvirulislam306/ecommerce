<?php

namespace Modules\Marketing\Models;

use App\Core\Support\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Story extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'media_path',
        'media_library_id',
        'action_url',
        'action_label',
        'is_active',
        'starts_at',
        'expires_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mediaUrl(): string
    {
        return '/storage/'.$this->media_path;
    }

    public function isExpired(): bool
    {
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return true;
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return true;
        }

        return false;
    }

    public function visibilityStatus(): string
    {
        if (! $this->is_active) {
            return 'hidden';
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return 'scheduled';
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return 'expired';
        }

        return 'visible';
    }
}
