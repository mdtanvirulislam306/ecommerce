<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Enums\PaymentMethod;

class OnlineOrder extends Model
{
    protected $fillable = [
        'number',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'payment_method',
        'currency',
        'subtotal',
        'grand_total',
        'notes',
        'warehouse_id',
        'confirmed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OnlineOrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OnlineOrderItem::class)->orderBy('sort_order');
    }
}
