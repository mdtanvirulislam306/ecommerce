<?php

namespace Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sales\Enums\SalesOrderStatusField;

class SalesOrderStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sales_order_id',
        'field',
        'from_value',
        'to_value',
        'note',
        'changed_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'field' => SalesOrderStatusField::class,
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
