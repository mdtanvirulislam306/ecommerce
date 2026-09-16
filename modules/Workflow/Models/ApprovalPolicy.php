<?php

namespace Modules\Workflow\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ApprovalPolicy extends Model
{
    use BelongsToTenant;

    protected $table = 'approval_policies';

    protected $fillable = [
        'name',
        'entity_type',
        'steps',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
