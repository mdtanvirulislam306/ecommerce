<?php

namespace Modules\Workflow\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WorkflowDefinition extends Model
{
    use BelongsToTenant;

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
