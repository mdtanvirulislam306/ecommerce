<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Modules\Ecommerce\Enums\PaymentMethod;

class PaymentSettingService extends Service
{
    /** SSLCommerz rejects sessions below this amount. */
    public const MINIMUM_ONLINE_AMOUNT = 10.0;

    public function __construct(private readonly StoreSettingService $settings) {}

    public function cashOnDeliveryEnabled(): bool
    {
        return (string) $this->settings->get('payment_cod_enabled', '1') === '1';
    }

    /**
     * Online payment is only offered once the shop has entered its gateway credentials.
     */
    public function onlineEnabled(): bool
    {
        return (string) $this->settings->get('payment_online_enabled', '0') === '1'
            && $this->sslcommerzCredentials() !== null;
    }

    /**
     * @return array{store_id: string, store_password: string, sandbox: bool}|null
     */
    public function sslcommerzCredentials(): ?array
    {
        $stored = $this->settings->getMany(
            ['sslcommerz_store_id', 'sslcommerz_store_password', 'sslcommerz_sandbox'],
            ['sslcommerz_sandbox' => '1'],
        );

        $password = $this->decrypt($stored['sslcommerz_store_password']);

        if (blank($stored['sslcommerz_store_id']) || blank($password)) {
            return null;
        }

        return [
            'store_id' => (string) $stored['sslcommerz_store_id'],
            'store_password' => $password,
            'sandbox' => (string) $stored['sslcommerz_sandbox'] === '1',
        ];
    }

    /**
     * Payment choices shown to the shopper at checkout, in display order.
     *
     * @return list<array{code: string, label: string, description: string, minimum: float|null}>
     */
    public function checkoutOptions(): array
    {
        $options = [];

        if ($this->onlineEnabled()) {
            $options[] = [
                'code' => PaymentMethod::Online->value,
                'label' => 'Pay online',
                'description' => 'bKash, Nagad, Rocket or card — secured by SSLCommerz',
                'minimum' => self::MINIMUM_ONLINE_AMOUNT,
            ];
        }

        if ($this->cashOnDeliveryEnabled()) {
            $options[] = [
                'code' => PaymentMethod::Cod->value,
                'label' => PaymentMethod::Cod->label(),
                'description' => 'Pay in cash when your order arrives',
                'minimum' => null,
            ];
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public function enabledMethodCodes(): array
    {
        return array_column($this->checkoutOptions(), 'code');
    }

    /**
     * Admin form values. The saved password is never sent back to the browser.
     *
     * @return array{guest_checkout: bool, require_phone: bool, payment_cod_enabled: bool, payment_online_enabled: bool, sslcommerz_store_id: string, sslcommerz_sandbox: bool, has_store_password: bool}
     */
    public function adminSettings(): array
    {
        $stored = $this->settings->getMany(
            ['guest_checkout', 'require_phone', 'payment_cod_enabled', 'payment_online_enabled', 'sslcommerz_store_id', 'sslcommerz_store_password', 'sslcommerz_sandbox'],
            ['guest_checkout' => '1', 'require_phone' => '0', 'payment_cod_enabled' => '1', 'payment_online_enabled' => '0', 'sslcommerz_sandbox' => '1'],
        );

        return [
            'guest_checkout' => $stored['guest_checkout'] === '1',
            'require_phone' => $stored['require_phone'] === '1',
            'payment_cod_enabled' => $stored['payment_cod_enabled'] === '1',
            'payment_online_enabled' => $stored['payment_online_enabled'] === '1',
            'sslcommerz_store_id' => (string) $stored['sslcommerz_store_id'],
            'sslcommerz_sandbox' => $stored['sslcommerz_sandbox'] === '1',
            'has_store_password' => filled($this->decrypt($stored['sslcommerz_store_password'])),
        ];
    }

    public function hasStorePassword(): bool
    {
        return filled($this->decrypt($this->settings->get('sslcommerz_store_password')));
    }

    public function guestCheckoutAllowed(): bool
    {
        return (string) $this->settings->get('guest_checkout', '1') === '1';
    }

    public function requiresPhone(): bool
    {
        return (string) $this->settings->get('require_phone', '0') === '1';
    }

    /**
     * @param  array{guest_checkout: bool, require_phone: bool, payment_cod_enabled: bool, payment_online_enabled: bool, sslcommerz_store_id?: string|null, sslcommerz_store_password?: string|null, sslcommerz_sandbox: bool}  $data
     */
    public function save(array $data): void
    {
        $values = [
            'guest_checkout' => $data['guest_checkout'] ? '1' : '0',
            'require_phone' => $data['require_phone'] ? '1' : '0',
            'payment_cod_enabled' => $data['payment_cod_enabled'] ? '1' : '0',
            'payment_online_enabled' => $data['payment_online_enabled'] ? '1' : '0',
            'sslcommerz_store_id' => trim((string) ($data['sslcommerz_store_id'] ?? '')),
            'sslcommerz_sandbox' => $data['sslcommerz_sandbox'] ? '1' : '0',
        ];

        if (filled($data['sslcommerz_store_password'] ?? null)) {
            $values['sslcommerz_store_password'] = Crypt::encryptString($data['sslcommerz_store_password']);
        }

        $this->settings->putMany($values);
    }

    private function decrypt(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException) {
            return null;
        }
    }
}
