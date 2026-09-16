<?php

namespace Modules\Hrm\Models;

use App\Core\Support\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use BelongsToTenant;

    protected $table = 'hrm_employees';

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'department',
        'designation',
        'hired_at',
        'is_active',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
