<?php

namespace Modules\Hrm\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    use BelongsToTenant;

    protected $table = 'hrm_salary_structures';

    protected $fillable = [
        'name',
        'employee_id',
        'designation_id',
        'components',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'components' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }
}
