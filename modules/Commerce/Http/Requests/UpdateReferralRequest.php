<?php

namespace Modules\Commerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Commerce\Models\Referral;

class UpdateReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Referral $referral */
        $referral = $this->route('referral');

        return [
            'code' => ['required', 'string', 'max:40', Rule::unique('referrals', 'code')->ignore($referral->id)],
            'referrer_name' => ['required', 'string', 'max:255'],
            'referrer_email' => ['nullable', 'email', 'max:255'],
            'referee_email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'string', Rule::in(['pending', 'completed', 'cancelled'])],
            'reward_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
