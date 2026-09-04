<?php

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowLog extends Model
{
    protected $table = 'workflow_logs';

    protected $fillable = [
        'source',
        'message',
        'level',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }
}
