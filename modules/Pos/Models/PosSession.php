<?php

namespace Modules\Pos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Pos\Enums\PosSessionStatus;

class PosSession extends Model
{
    protected $fillable = [
        'pos_register_id',
        'opened_by',
        'closed_by',
        'status',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PosSessionStatus::class,
            'opening_cash' => 'decimal:4',
            'closing_cash' => 'decimal:4',
            'expected_cash' => 'decimal:4',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class);
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
}
