<?php

namespace Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Sales\Enums\SalesReturnStatus;

class SalesReturn extends Model
{
    protected $fillable = [
        'number',
        'sales_invoice_id',
        'sales_order_id',
        'status',
        'warehouse_id',
        'customer_name',
        'currency',
        'subtotal',
        'grand_total',
        'notes',
        'confirmed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesReturnStatus::class,
            'subtotal' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'confirmed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class)->orderBy('sort_order');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
