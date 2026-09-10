<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsFormSubmission extends Model
{
    protected $fillable = [
        'cms_page_id',
        'widget_id',
        'type',
        'payload',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }
}
