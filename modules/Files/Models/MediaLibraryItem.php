<?php

namespace Modules\Files\Models;

use Illuminate\Database\Eloquent\Model;

class MediaLibraryItem extends Model
{
    protected $fillable = ['name', 'disk', 'path', 'mime', 'size', 'uploaded_by'];
}
