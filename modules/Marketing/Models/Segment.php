<?php

namespace Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Crm\Models\CustomerSegment;

class Segment extends Model
{
    protected $table = 'marketing_segments';

    protected $fillable = [
        'name',
        'description',
        'customer_segment_id',
        'rules',
        'customer_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'customer_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function customerSegment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id');
    }
}
