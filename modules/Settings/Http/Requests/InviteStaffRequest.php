<?php

namespace Modules\Settings\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Rules\AssignableRole;

class InviteStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', TenantRule::unique('users', 'email')],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'distinct', TenantRule::exists('roles', 'id'), new AssignableRole($this->user())],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'Someone on your team already uses this email.',
            'role_ids.required' => 'Pick at least one role so they can open something after signing in.',
            'role_ids.min' => 'Pick at least one role so they can open something after signing in.',
        ];
    }
}
