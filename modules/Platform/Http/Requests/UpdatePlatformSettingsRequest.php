<?php

namespace Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Platform\Services\PlatformSettingService;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_platform_admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:100'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'default_plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
            'mail_mailer' => ['required', Rule::in([PlatformSettingService::MAILER_SERVER, PlatformSettingService::MAILER_SMTP])],
            'mail_host' => ['nullable', 'required_if:mail_mailer,smtp', 'string', 'max:255'],
            'mail_port' => ['nullable', 'required_if:mail_mailer,smtp', 'integer', 'between:1,65535'],
            'mail_encryption' => ['nullable', 'required_if:mail_mailer,smtp', Rule::in(['tls', 'ssl', 'none'])],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'required_if:mail_mailer,smtp', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:100'],
            'sslcommerz_store_id' => ['nullable', 'string', 'max:100'],
            'sslcommerz_store_password' => ['nullable', 'string', 'max:255'],
            'sslcommerz_sandbox' => ['boolean'],
            'maintenance_enabled' => ['boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'default_plan_id.exists' => 'Pick a plan that is on offer.',
            'mail_host.required_if' => 'Enter the SMTP server, like smtp.gmail.com.',
            'mail_port.required_if' => 'Enter the SMTP port, usually 587 or 465.',
            'mail_from_address.required_if' => 'Enter the address emails are sent from.',
        ];
    }
}
