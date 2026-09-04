<?php

namespace Modules\Hrm\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'hrm_departments';

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
