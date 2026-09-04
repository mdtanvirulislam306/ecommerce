<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalYear extends Model
{
    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_closed', 'is_current'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_closed' => 'boolean',
            'is_current' => 'boolean',
        ];
    }

    public function closings(): HasMany
    {
        return $this->hasMany(FinancialClosing::class);
    }
}
