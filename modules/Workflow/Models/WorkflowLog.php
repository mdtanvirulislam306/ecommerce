<?php

namespace Modules\Workflow\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WorkflowLog extends Model
{
    use BelongsToTenant;

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
