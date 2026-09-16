<?php

namespace Modules\Accounting\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetDepreciation extends Model
{
    use BelongsToTenant;

    protected $fillable = ['fixed_asset_id', 'period_date', 'amount', 'notes'];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'amount' => 'decimal:4',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
