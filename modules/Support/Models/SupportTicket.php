<?php

namespace Modules\Support\Models;

use App\Core\Support\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Support\Enums\TicketPriority;
use Modules\Support\Enums\TicketStatus;

class SupportTicket extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'number',
        'subject',
        'body',
        'status',
        'priority',
        'requester_name',
        'requester_email',
        'assigned_to',
        'created_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
