<?php

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledTask extends Model
{
    protected $table = 'scheduled_tasks';

    protected $fillable = [
        'name',
        'cron',
        'handler',
        'is_active',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }
}
