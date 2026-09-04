<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class CmsMenu extends Model
{
    protected $fillable = [
        'name',
        'location',
        'items',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
