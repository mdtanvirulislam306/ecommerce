<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class StoreDomain extends Model
{
    protected $fillable = [
        'domain',
        'is_primary',
        'is_active',
        'ssl_status',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
