<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
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
