<?php

namespace Modules\Settings\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Rules\AssignableRole;

class AssignUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role_ids' => ['present', 'array'],
            'role_ids.*' => ['integer', 'distinct', TenantRule::exists('roles', 'id'), new AssignableRole($this->user())],
        ];
    }
}
