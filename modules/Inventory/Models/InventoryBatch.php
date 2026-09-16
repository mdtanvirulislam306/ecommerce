<?php

namespace Modules\Inventory\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class InventoryBatch extends Model
{
    use BelongsToTenant;

    protected $table = 'inventory_batches';

    protected $fillable = [
        'batch_number',
        'product_id',
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'manufactured_at',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'manufactured_at' => 'date',
            'expires_at' => 'date',
        ];
    }
}
