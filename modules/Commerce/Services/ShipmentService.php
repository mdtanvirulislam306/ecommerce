<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Commerce\Models\Shipment;

class ShipmentService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Shipment::query()
            ->with('courier:id,name,code')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('tracking_number', 'like', "%{$search}%")
                        ->orWhere('recipient_name', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listForTracking(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return $this->listPaginated($search, $perPage);
    }

    public function create(array $data): Shipment
    {
        return Shipment::query()->create([
            'courier_id' => $data['courier_id'] ?? null,
            'tracking_number' => $data['tracking_number'],
            'status' => $data['status'] ?? 'pending',
            'recipient_name' => $data['recipient_name'],
            'destination' => $data['destination'] ?? null,
            'shipped_at' => $data['shipped_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function update(Shipment $shipment, array $data): Shipment
    {
        $shipment->update([
            'courier_id' => array_key_exists('courier_id', $data) ? $data['courier_id'] : $shipment->courier_id,
            'tracking_number' => $data['tracking_number'] ?? $shipment->tracking_number,
            'status' => $data['status'] ?? $shipment->status,
            'recipient_name' => $data['recipient_name'] ?? $shipment->recipient_name,
            'destination' => array_key_exists('destination', $data) ? $data['destination'] : $shipment->destination,
            'shipped_at' => array_key_exists('shipped_at', $data) ? $data['shipped_at'] : $shipment->shipped_at,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $shipment->notes,
        ]);

        return $shipment->fresh();
    }

    public function delete(Shipment $shipment): void
    {
        $shipment->delete();
    }
}
