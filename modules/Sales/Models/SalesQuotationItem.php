<?php

namespace Modules\Sales\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuotationItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'sales_quotation_id',
        'product_id',
        'product_variant_id',
        'sku',
        'name',
        'quantity',
        'unit_price',
        'line_total',
        'currency',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'sales_quotation_id');
    }
}
