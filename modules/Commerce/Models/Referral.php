<?php

namespace Modules\Commerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use BelongsToTenant;

    protected $table = 'referrals';

    protected $fillable = [
        'code',
        'referrer_name',
        'referrer_email',
        'referee_email',
        'status',
        'reward_amount',
    ];

    protected function casts(): array
    {
        return [
            'reward_amount' => 'decimal:2',
        ];
    }
}
