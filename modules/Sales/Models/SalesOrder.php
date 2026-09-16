<?php

namespace Modules\Sales\Models;

use App\Core\Support\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Crm\Models\Customer;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Enums\SalesPaymentStatus;

class SalesOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'number',
        'status',
        'delivery_status',
        'payment_status',
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
        'amount_paid',
        'amount_due',
        'notes',
        'confirmed_at',
        'cancelled_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatus::class,
            'delivery_status' => SalesDeliveryStatus::class,
            'payment_status' => SalesPaymentStatus::class,
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'amount_paid' => 'decimal:4',
            'amount_due' => 'decimal:4',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class)->orderBy('sort_order');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'sales_order_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SalesOrderStatusLog::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
