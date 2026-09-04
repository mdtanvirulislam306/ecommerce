<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventorySerialNumber extends Model
{
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
