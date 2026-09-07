<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Sales\Enums\SalesDeliveryStatus;

class UpdateSalesOrderLineDeliveryRequest extends FormRequest
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
        return [
            'delivery_status' => [
                'required',
                Rule::enum(SalesDeliveryStatus::class),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $status = SalesDeliveryStatus::tryFrom((string) $value);
                    if ($status && ! $status->isLineApplicable()) {
                        $fail('Partial is an order-level status. Set each line individually.');
                    }
                },
            ],
            'quantity_delivered' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
