<?php

namespace Modules\Files\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use BelongsToTenant;

    protected $fillable = ['title', 'disk', 'path', 'mime', 'size', 'uploaded_by'];
}
