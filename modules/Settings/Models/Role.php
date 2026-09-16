<?php

namespace Modules\Settings\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use BelongsToTenant;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];
}
