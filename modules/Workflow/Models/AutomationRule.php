<?php

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    protected $table = 'automation_rules';

    protected $fillable = [
        'name',
        'event',
        'conditions',
        'actions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
