<?php

namespace Modules\Pos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Crm\Models\Customer;
use Modules\Pos\Enums\PosOrderStatus;

class PosOrder extends Model
{
    protected $fillable = [
        'number',
        'status',
        'pos_register_id',
        'pos_session_id',
        'warehouse_id',
        'customer_id',
        'customer_name',
        'payment_method',
        'payment_reference',
        'currency',
        'subtotal',
        'discount_total',
        'grand_total',
        'amount_tendered',
        'change_due',
        'notes',
        'created_by',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PosOrderStatus::class,
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'amount_tendered' => 'decimal:4',
            'change_due' => 'decimal:4',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosOrderItem::class)->orderBy('sort_order');
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
