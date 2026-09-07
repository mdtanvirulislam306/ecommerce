<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sales\Enums\SalesDeliveryStatus;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id',
        'product_id',
        'product_variant_id',
        'sku',
        'name',
        'quantity',
        'unit_price',
        'line_total',
        'currency',
        'delivery_status',
        'quantity_delivered',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'line_total' => 'decimal:4',
            'quantity_delivered' => 'decimal:4',
            'delivery_status' => SalesDeliveryStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }
}
