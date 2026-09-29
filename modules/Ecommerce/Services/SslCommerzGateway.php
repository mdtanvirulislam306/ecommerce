<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Ecommerce\Exceptions\PaymentGatewayException;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Models\OnlineOrderItem;

class SslCommerzGateway
{
    private const LIVE_URL = 'https://securepay.sslcommerz.com';

    private const SANDBOX_URL = 'https://sandbox.sslcommerz.com';

    public function __construct(private readonly PaymentSettingService $payments) {}

    /**
     * Opens a hosted payment session and returns the SSLCommerz page the shopper must be sent to.
     *
     * @throws PaymentGatewayException
     */
    public function createSession(OnlineOrder $order, string $transactionId): string
    {
        $credentials = $this->credentials();
        $order->loadMissing('items');

        $address = Str::limit(trim(preg_replace('/\s+/', ' ', $order->shipping_address)), 200, '');
        $email = $order->customer_email ?: (string) config('mail.from.address');
        $productNames = Str::limit($order->items->pluck('name')->implode(', '), 250, '');

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->baseUrl($credentials).'/gwprocess/v4/api.php', [
                    'store_id' => $credentials['store_id'],
                    'store_passwd' => $credentials['store_password'],
                    'total_amount' => number_format((float) $order->grand_total, 2, '.', ''),
                    'currency' => $order->currency,
                    'tran_id' => $transactionId,
                    'success_url' => route('shop.payments.sslcommerz.success'),
                    'fail_url' => route('shop.payments.sslcommerz.fail'),
                    'cancel_url' => route('shop.payments.sslcommerz.cancel'),
                    'ipn_url' => route('shop.payments.sslcommerz.ipn'),
                    'cus_name' => $order->customer_name,
                    'cus_email' => $email,
                    'cus_add1' => $address,
                    'cus_city' => $order->delivery_zone ?: 'Dhaka',
                    'cus_postcode' => '1000',
                    'cus_country' => 'Bangladesh',
                    'cus_phone' => (string) $order->customer_phone,
                    'shipping_method' => 'Courier',
                    'ship_name' => $order->customer_name,
                    'ship_add1' => $address,
                    'ship_city' => $order->delivery_zone ?: 'Dhaka',
                    'ship_postcode' => '1000',
                    'ship_country' => 'Bangladesh',
                    'num_of_item' => $order->items->sum(fn (OnlineOrderItem $item) => (int) ceil((float) $item->quantity)),
                    'product_name' => $productNames !== '' ? $productNames : $order->number,
                    'product_category' => 'general',
                    'product_profile' => 'physical-goods',
                    'value_a' => $order->access_token,
                ]);
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayException('The payment gateway could not be reached.', previous: $exception);
        }

        $gatewayUrl = $response->json('GatewayPageURL');

        if ($response->json('status') !== 'SUCCESS' || blank($gatewayUrl)) {
            throw new PaymentGatewayException(
                'SSLCommerz refused the payment session: '.($response->json('failedreason') ?: 'HTTP '.$response->status()),
            );
        }

        return (string) $gatewayUrl;
    }

    /**
     * Asks SSLCommerz directly whether a payment happened; callback bodies can be forged.
     *
     * @return array<string, mixed>
     *
     * @throws PaymentGatewayException
     */
    public function validate(string $validationId): array
    {
        $credentials = $this->credentials();

        try {
            $response = Http::timeout(30)->get($this->baseUrl($credentials).'/validator/api/validationserverAPI.php', [
                'val_id' => $validationId,
                'store_id' => $credentials['store_id'],
                'store_passwd' => $credentials['store_password'],
                'format' => 'json',
            ]);
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayException('The payment gateway could not be reached.', previous: $exception);
        }

        return $response->successful() ? (array) $response->json() : [];
    }

    /**
     * @return array{store_id: string, store_password: string, sandbox: bool}
     */
    private function credentials(): array
    {
        return $this->payments->sslcommerzCredentials()
            ?? throw new PaymentGatewayException('Online payment is not configured for this shop.');
    }

    /**
     * @param  array{sandbox: bool}  $credentials
     */
    private function baseUrl(array $credentials): string
    {
        return $credentials['sandbox'] ? self::SANDBOX_URL : self::LIVE_URL;
    }
}
