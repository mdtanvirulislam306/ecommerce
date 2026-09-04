<?php

namespace Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sales\Enums\CreditNoteStatus;

class SalesCreditNote extends Model
{
    protected $fillable = [
        'number',
        'sales_invoice_id',
        'status',
        'amount',
        'currency',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CreditNoteStatus::class,
            'amount' => 'decimal:4',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
