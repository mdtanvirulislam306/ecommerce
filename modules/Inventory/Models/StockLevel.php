<?php

namespace Modules\Inventory\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'on_hand',
        'reserved',
        'reorder_point',
    ];

    protected function casts(): array
    {
        return [
            'on_hand' => 'decimal:4',
            'reserved' => 'decimal:4',
            'reorder_point' => 'decimal:4',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function available(): string
    {
        return number_format(max(0, (float) $this->on_hand - (float) $this->reserved), 4, '.', '');
    }
}
