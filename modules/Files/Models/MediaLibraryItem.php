<?php

namespace Modules\Files\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MediaLibraryItem extends Model
{
    use BelongsToTenant;

    protected $fillable = ['name', 'disk', 'path', 'mime', 'size', 'uploaded_by'];
}
