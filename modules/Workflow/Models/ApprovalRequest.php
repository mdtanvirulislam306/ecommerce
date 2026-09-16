<?php

namespace Modules\Workflow\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use BelongsToTenant;

    protected $table = 'approval_requests';

    protected $fillable = [
        'title',
        'status',
        'requested_by',
        'approver_id',
        'notes',
    ];
}
