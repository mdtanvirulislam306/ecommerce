<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'price_monthly',
        'currency',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'price_monthly' => 'integer',
        ];
    }

    public function modules(): HasMany
    {
        return $this->hasMany(PlanModule::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * @return list<string>
     */
    public function moduleCodes(): array
    {
        return $this->modules()->pluck('module_code')->all();
    }
}
