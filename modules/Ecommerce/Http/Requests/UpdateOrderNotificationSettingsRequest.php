<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notify_customer_email' => $this->boolean('notify_customer_email'),
            'notify_customer_sms' => $this->boolean('notify_customer_sms'),
            'notify_staff_email' => $this->boolean('notify_staff_email'),
            'notify_staff_sms' => $this->boolean('notify_staff_sms'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notify_customer_email' => ['boolean'],
            'notify_customer_sms' => ['boolean'],
            'notify_staff_email' => ['boolean'],
            'notify_staff_sms' => ['boolean'],
            'staff_notification_email' => ['nullable', 'email', 'max:120'],
            'staff_notification_phone' => ['nullable', 'required_if:notify_staff_sms,true', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s\-]{9,18}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'staff_notification_email.email' => 'Enter a valid email address.',
            'staff_notification_phone.required_if' => 'Add the mobile number that should receive new-order texts.',
            'staff_notification_phone.regex' => 'Enter a valid mobile number, for example 01711-223344.',
        ];
    }
}
