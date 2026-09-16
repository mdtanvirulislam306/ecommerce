<?php

namespace Modules\Ecommerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsPage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'title',
        'slug',
        'body',
        'blocks',
        'is_published',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(CmsFormSubmission::class);
    }
}
