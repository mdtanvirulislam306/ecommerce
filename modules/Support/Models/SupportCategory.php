<?php

namespace Modules\Support\Models;

use Illuminate\Database\Eloquent\Model;

class SupportCategory extends Model
{
    protected $table = 'support_categories';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
