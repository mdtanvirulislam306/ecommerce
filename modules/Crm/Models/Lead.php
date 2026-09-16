<?php

namespace Modules\Crm\Models;

use App\Core\Support\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Crm\Enums\LeadStage;

class Lead extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'source',
        'source_id',
        'stage',
        'customer_group_id',
        'notes',
        'assigned_to',
        'converted_customer_id',
        'converted_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'stage' => LeadStage::class,
            'converted_at' => 'datetime',
        ];
    }

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class)->orderByDesc('created_at');
    }

    /**
     * Earliest incomplete activity with a due date (overdue first by chronological min).
     */
    public function nextAction(): HasOne
    {
        return $this->hasOne(CrmActivity::class)->ofMany(
            ['due_at' => 'min'],
            function ($query) {
                $query->whereNull('completed_at')->whereNotNull('due_at');
            },
        );
    }

    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
