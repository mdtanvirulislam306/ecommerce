<?php

namespace Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Marketing\Enums\ReferralStatus;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'referee_email' => $this->input('referee_email') ?: null,
            'status' => $this->input('status') ?: ReferralStatus::Pending->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', 'unique:marketing_referrals,code'],
            'referrer_name' => ['required', 'string', 'max:255'],
            'referrer_email' => ['required', 'email', 'max:255'],
            'referee_email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', Rule::enum(ReferralStatus::class)],
            'reward_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
