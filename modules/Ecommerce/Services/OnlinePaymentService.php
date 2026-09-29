<?php

namespace Modules\Ecommerce\Services;

use App\Core\Events\OnlineOrderPlaced;
use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Enums\PaymentMethod;
use Modules\Ecommerce\Enums\PaymentStatus;
use Modules\Ecommerce\Exceptions\PaymentGatewayException;
use Modules\Ecommerce\Models\OnlineOrder;

class OnlinePaymentService extends Service
{
    private const VALID_STATUSES = ['VALID', 'VALIDATED'];

    public function __construct(
        private readonly SslCommerzGateway $gateway,
        private readonly PaymentSettingService $payments,
    ) {}

    public function canPayOnline(OnlineOrder $order): bool
    {
        return $order->payment_method === PaymentMethod::Online
            && $order->status === OnlineOrderStatus::Pending
            && in_array($order->payment_status, [PaymentStatus::Pending, PaymentStatus::Failed], true)
            && $this->payments->onlineEnabled();
    }

    public function canSwitchToCashOnDelivery(OnlineOrder $order): bool
    {
        return $order->payment_method === PaymentMethod::Online
            && $order->status === OnlineOrderStatus::Pending
            && $order->payment_status !== PaymentStatus::Paid
            && $this->payments->cashOnDeliveryEnabled();
    }

    /**
     * Every attempt gets a fresh transaction id, so a late callback from an abandoned attempt cannot fail a newer one.
     *
     * @throws PaymentGatewayException
     */
    public function startSession(OnlineOrder $order): string
    {
        if (! $this->canPayOnline($order)) {
            throw ValidationException::withMessages(['payment' => 'This order can no longer be paid online.']);
        }

        $transactionId = $order->number.'-'.Str::upper(Str::random(6));

        $order->update([
            'payment_status' => PaymentStatus::Pending,
            'payment_transaction_id' => $transactionId,
        ]);

        return $this->gateway->createSession($order, $transactionId);
    }

    /**
     * Marks the order paid only after SSLCommerz itself vouches for the payment and its amount.
     * Safe to call repeatedly: the browser redirect and the IPN both arrive for the same payment.
     *
     * @throws PaymentGatewayException
     */
    public function confirmPayment(?string $validationId): ?OnlineOrder
    {
        if (blank($validationId)) {
            return null;
        }

        $payment = $this->gateway->validate($validationId);

        if (! in_array($payment['status'] ?? null, self::VALID_STATUSES, true)) {
            return null;
        }

        $order = $this->findOrder($payment['value_a'] ?? null);
        $transactionId = (string) ($payment['tran_id'] ?? '');

        if ($order === null || ! str_starts_with($transactionId, $order->number.'-')) {
            return null;
        }

        if (! $this->amountMatches($order, $payment)) {
            report(new PaymentGatewayException(
                "SSLCommerz payment {$transactionId} does not match order {$order->number}: "
                .($payment['currency_amount'] ?? '?').' '.($payment['currency_type'] ?? '?'),
            ));

            return null;
        }

        $isFirstConfirmation = DB::transaction(function () use ($order, $payment, $transactionId, $validationId) {
            $locked = OnlineOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status === PaymentStatus::Paid) {
                return false;
            }

            $locked->update([
                'payment_method' => PaymentMethod::Online,
                'payment_status' => PaymentStatus::Paid,
                'payment_transaction_id' => $transactionId,
                'payment_validation_id' => $validationId,
                'payment_bank_transaction_id' => $payment['bank_tran_id'] ?? null,
                'payment_card_type' => $payment['card_type'] ?? null,
                'paid_at' => now(),
            ]);

            return true;
        });

        $order->refresh();

        if ($isFirstConfirmation && $order->status === OnlineOrderStatus::Pending) {
            event(new OnlineOrderPlaced(orderId: $order->id, orderNumber: $order->number));
        }

        return $order;
    }

    /**
     * Only the attempt currently in flight can be marked failed; stale callbacks are ignored.
     */
    public function markFailed(?string $accessToken, ?string $transactionId): ?OnlineOrder
    {
        $order = $this->findOrder($accessToken);

        if ($order === null) {
            return null;
        }

        OnlineOrder::query()
            ->whereKey($order->id)
            ->where('payment_status', PaymentStatus::Pending->value)
            ->where('payment_transaction_id', (string) $transactionId)
            ->update(['payment_status' => PaymentStatus::Failed->value]);

        return $order->refresh();
    }

    /**
     * The shop hears about an online order only once it is paid, or once the shopper falls back to cash.
     */
    public function switchToCashOnDelivery(OnlineOrder $order): OnlineOrder
    {
        $switched = DB::transaction(function () use ($order) {
            $locked = OnlineOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $this->canSwitchToCashOnDelivery($locked)) {
                return false;
            }

            $locked->update([
                'payment_method' => PaymentMethod::Cod,
                'payment_status' => PaymentStatus::Unpaid,
            ]);

            return true;
        });

        if (! $switched) {
            throw ValidationException::withMessages(['payment' => 'This order can no longer switch to cash on delivery.']);
        }

        $order->refresh();
        event(new OnlineOrderPlaced(orderId: $order->id, orderNumber: $order->number));

        return $order;
    }

    private function findOrder(mixed $accessToken): ?OnlineOrder
    {
        if (! is_string($accessToken) || strlen($accessToken) !== 40) {
            return null;
        }

        return OnlineOrder::query()->where('access_token', $accessToken)->first();
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function amountMatches(OnlineOrder $order, array $payment): bool
    {
        $currency = (string) ($payment['currency_type'] ?? $payment['currency'] ?? '');
        $amount = $payment['currency_amount'] ?? $payment['amount'] ?? null;

        return strtoupper($currency) === strtoupper($order->currency)
            && is_numeric($amount)
            && abs((float) $amount - (float) $order->grand_total) < 0.01;
    }
}
