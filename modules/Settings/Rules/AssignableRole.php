<?php

namespace Modules\Settings\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Settings\Models\Role;

/**
 * Staff who manage the team may only hand out roles they could have built themselves,
 * so nobody can grant a colleague more access than they hold.
 */
class AssignableRole implements ValidationRule
{
    public function __construct(private readonly ?User $actor) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->actor === null || $this->actor->hasFullShopAccess()) {
            return;
        }

        $granted = Role::query()->find($value)?->permissions ?? [];

        if (array_diff($granted, $this->actor->permissionKeys()) !== []) {
            $fail('You can only assign roles that have no more access than your own.');
        }
    }
}
