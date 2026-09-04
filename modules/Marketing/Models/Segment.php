<?php

namespace Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    protected $table = 'marketing_segments';

    protected $fillable = [
        'name',
        'description',
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
}
