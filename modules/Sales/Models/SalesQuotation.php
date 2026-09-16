<?php

namespace Modules\Sales\Models;

use App\Core\Support\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Crm\Models\Customer;
use Modules\Sales\Enums\QuotationStatus;

class SalesQuotation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'number',
        'status',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_group_id',
        'warehouse_id',
        'currency',
        'subtotal',
        'tax_total',
        'grand_total',
        'notes',
        'valid_until',
        'sales_order_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'valid_until' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesQuotationItem::class)->orderBy('sort_order');
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
