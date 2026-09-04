<?php

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowDefinition extends Model
{
    protected $table = 'workflows';

    protected $fillable = [
        'name',
        'trigger',
        'is_active',
        'definition',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'definition' => 'array',
        ];
    }
}
