<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    protected $fillable = [
        'bank_account_id', 'txn_date', 'type', 'amount', 'reference', 'memo', 'is_reconciled',
    ];

    protected function casts(): array
    {
        return [
            'txn_date' => 'date',
            'amount' => 'decimal:4',
            'is_reconciled' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
