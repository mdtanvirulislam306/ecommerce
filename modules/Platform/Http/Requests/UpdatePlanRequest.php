<?php

namespace Modules\Platform\Http\Requests;

use App\Core\Module\ModuleManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Billing\Models\Plan;

class UpdatePlanRequest extends FormRequest
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
        /** @var Plan $plan */
        $plan = $this->route('plan');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_monthly' => ['required', 'integer', 'min:0', 'max:100000000'],
            'is_active' => ['boolean', Rule::when($plan->is_default, ['accepted'])],
            'is_default' => ['boolean'],
            'module_codes' => ['array'],
            'module_codes.*' => ['string', Rule::in(array_keys(app(ModuleManager::class)->all()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_active.accepted' => 'New shops start on this plan, so it has to stay on offer. Make another plan the default first.',
            'module_codes.*.in' => 'One of the chosen modules does not exist.',
        ];
    }
}
