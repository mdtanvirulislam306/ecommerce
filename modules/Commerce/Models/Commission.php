<?php

namespace Modules\Commerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use BelongsToTenant;

    protected $table = 'commissions';

    protected $fillable = [
        'name',
        'type',
        'rate',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }
}
