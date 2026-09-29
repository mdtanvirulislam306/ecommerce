<?php

namespace Modules\Commerce\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Commerce\Models\Shipment;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'courier_id' => $this->input('courier_id') ?: null,
            'shipped_at' => $this->input('shipped_at') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Shipment $shipment */
        $shipment = $this->route('shipment');

        return [
            'courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
            'tracking_number' => ['required', 'string', 'max:100', TenantRule::unique('shipments', 'tracking_number')->ignore($shipment->id)],
            'status' => ['required', 'string', Rule::in(['pending', 'shipped', 'in_transit', 'delivered', 'cancelled'])],
            'recipient_name' => ['required', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'shipped_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
