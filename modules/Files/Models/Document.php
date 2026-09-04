<?php

namespace Modules\Files\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['title', 'disk', 'path', 'mime', 'size', 'uploaded_by'];
}
