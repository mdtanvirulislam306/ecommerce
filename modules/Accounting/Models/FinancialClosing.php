<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialClosing extends Model
{
    protected $fillable = ['fiscal_year_id', 'closed_on', 'notes', 'closed_by'];

    protected function casts(): array
    {
        return ['closed_on' => 'date'];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }
}
