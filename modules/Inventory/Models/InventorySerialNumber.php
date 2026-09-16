<?php

namespace Modules\Inventory\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class InventorySerialNumber extends Model
{
    use BelongsToTenant;

    protected $table = 'inventory_serial_numbers';

    protected $fillable = [
        'serial_number',
        'product_id',
        'product_variant_id',
        'warehouse_id',
        'status',
        'notes',
    ];
}
