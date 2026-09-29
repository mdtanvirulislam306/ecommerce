<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeliveryRateService extends Service
{
    public function __construct(private readonly StoreSettingService $settings) {}

    /**
     * @return array{enabled: bool, zones: list<array{code: string, name: string, rate: float}>, free_threshold: float|null}
     */
    public function options(): array
    {
        $enabled = $this->enabled();

        return [
            'enabled' => $enabled,
            'zones' => $enabled ? $this->zones() : [],
            'free_threshold' => $enabled ? $this->freeThreshold() : null,
        ];
    }

    public function enabled(): bool
    {
        return (string) $this->settings->get('shipping_enabled', '1') === '1';
    }

    /**
     * Shops configured before zones existed keep their single default rate as one "Standard delivery" zone.
     *
     * @return list<array{code: string, name: string, rate: float}>
     */
    public function zones(): array
    {
        $stored = json_decode((string) $this->settings->get('delivery_zones', ''), true);

        $zones = is_array($stored) && $stored !== []
            ? $stored
            : [['name' => 'Standard delivery', 'rate' => (float) $this->settings->get('default_rate', '0')]];

        return collect($zones)
            ->filter(fn ($zone) => is_array($zone) && filled($zone['name'] ?? null))
            ->map(fn (array $zone) => [
                'code' => Str::slug($zone['name']),
                'name' => (string) $zone['name'],
                'rate' => round(max(0, (float) ($zone['rate'] ?? 0)), 2),
            ])
            ->values()
            ->all();
    }

    public function freeThreshold(): ?float
    {
        $threshold = $this->settings->get('free_shipping_threshold', '');

        return is_numeric($threshold) && (float) $threshold > 0 ? (float) $threshold : null;
    }

    /**
     * @return array{zone: string|null, fee: float}
     */
    public function quote(?string $zoneCode, float $orderAmount): array
    {
        if (! $this->enabled()) {
            return ['zone' => null, 'fee' => 0.0];
        }

        $zones = collect($this->zones());
        $zone = $zones->count() === 1 && $zoneCode === null
            ? $zones->first()
            : $zones->firstWhere('code', $zoneCode);

        if ($zone === null) {
            throw ValidationException::withMessages(['delivery_zone' => 'Please choose a delivery area.']);
        }

        $threshold = $this->freeThreshold();
        $fee = $threshold !== null && $orderAmount >= $threshold ? 0.0 : $zone['rate'];

        return ['zone' => $zone['name'], 'fee' => $fee];
    }
}
