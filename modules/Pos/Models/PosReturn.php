<?php

namespace Modules\Pos\Models;

use App\Core\Support\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosReturn extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'number',
        'status',
        'pos_order_id',
        'pos_session_id',
        'warehouse_id',
        'currency',
        'subtotal',
        'grand_total',
        'reason',
        'notes',
        'returned_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'returned_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosReturnItem::class)->orderBy('sort_order');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
