<?php

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    protected $fillable = [
        'title',
        'status',
        'requested_by',
        'approver_id',
        'notes',
    ];
}
