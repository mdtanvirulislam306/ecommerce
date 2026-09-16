<?php

namespace Modules\Commerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    use BelongsToTenant;

    protected $table = 'loyalty_transactions';

    protected $fillable = [
        'loyalty_point_id',
        'type',
        'points',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }

    public function loyaltyPoint(): BelongsTo
    {
        return $this->belongsTo(LoyaltyPoint::class);
    }
}
