<?php

namespace Modules\Marketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Crm\Models\CustomerSegment;
use Modules\Marketing\Enums\CampaignChannel;
use Modules\Marketing\Enums\CampaignStatus;

class Campaign extends Model
{
    protected $table = 'marketing_campaigns';

    protected $fillable = [
        'name',
        'channel',
        'status',
        'subject',
        'body',
        'scheduled_at',
        'sent_at',
        'audience_count',
        'customer_segment_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'channel' => CampaignChannel::class,
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customerSegment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id');
    }
}
