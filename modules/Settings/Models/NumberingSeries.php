<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class NumberingSeries extends Model
{
    protected $table = 'numbering_series';

    protected $fillable = [
        'name',
        'code',
        'prefix',
        'next_number',
        'pad_length',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
