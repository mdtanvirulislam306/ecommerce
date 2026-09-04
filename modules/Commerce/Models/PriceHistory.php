<?php

namespace Modules\Commerce\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Commerce\Enums\PriceChangeAction;

class PriceHistory extends Model
{
    protected $table = 'price_history';

    protected $fillable = [
        'price_list_id',
        'price_list_item_id',
        'product_id',
        'product_variant_id',
        'action',
        'old_price',
        'new_price',
        'min_quantity',
        'currency',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'action' => PriceChangeAction::class,
            'old_price' => 'decimal:4',
            'new_price' => 'decimal:4',
        ];
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
