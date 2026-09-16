<?php

namespace Modules\Marketing\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Marketing\Enums\ReferralStatus;

class Referral extends Model
{
    use BelongsToTenant;

    protected $table = 'marketing_referrals';

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
            'status' => ReferralStatus::class,
            'reward_amount' => 'decimal:2',
        ];
    }
}
