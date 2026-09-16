<?php

namespace Modules\Pos\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PosRegister extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'code',
        'warehouse_id',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function openSession(): HasOne
    {
        return $this->hasOne(PosSession::class)->where('status', 'open')->latestOfMany();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class);
    }
}
