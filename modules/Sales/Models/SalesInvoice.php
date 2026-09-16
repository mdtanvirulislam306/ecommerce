<?php

namespace Modules\Sales\Models;

use App\Core\Support\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Crm\Models\Customer;
use Modules\Sales\Enums\InvoiceStatus;

class SalesInvoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'number',
        'sales_order_id',
        'status',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'currency',
        'subtotal',
        'tax_total',
        'grand_total',
        'amount_paid',
        'amount_due',
        'due_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'amount_paid' => 'decimal:4',
            'amount_due' => 'decimal:4',
            'due_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalesPayment::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(SalesCreditNote::class);
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
