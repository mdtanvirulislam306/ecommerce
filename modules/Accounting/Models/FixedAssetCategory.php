<?php

namespace Modules\Accounting\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAssetCategory extends Model
{
    use BelongsToTenant;

    protected $fillable = ['name', 'code', 'useful_life_years', 'depreciation_rate'];

    protected function casts(): array
    {
        return [
            'useful_life_years' => 'decimal:2',
            'depreciation_rate' => 'decimal:4',
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }
}
