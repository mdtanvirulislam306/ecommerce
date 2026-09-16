<?php

namespace Modules\Ecommerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'key',
        'value',
    ];
}
