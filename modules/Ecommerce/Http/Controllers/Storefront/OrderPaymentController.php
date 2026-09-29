<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\Ecommerce\Exceptions\PaymentGatewayException;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Services\OnlinePaymentService;
use Symfony\Component\HttpFoundation\Response;

class OrderPaymentController extends Controller
{
    public function pay(string $token, OnlinePaymentService $payments): Response
    {
        $order = OnlineOrder::query()->where('access_token', $token)->firstOrFail();

        return self::redirectToGateway($order, $payments);
    }

    public function payOnDelivery(string $token, OnlinePaymentService $payments): RedirectResponse
    {
        $order = OnlineOrder::query()->where('access_token', $token)->firstOrFail();
        $payments->switchToCashOnDelivery($order);

        return redirect()
            ->route('shop.orders.show', $order->access_token)
            ->with('success', 'Done — you will pay in cash when your order arrives.');
    }

    /**
     * A gateway outage must not lose the order: the shopper lands on their order page and can retry or pay cash.
     */
    public static function redirectToGateway(OnlineOrder $order, OnlinePaymentService $payments): Response
    {
        try {
            return Inertia::location($payments->startSession($order));
        } catch (PaymentGatewayException $exception) {
            report($exception);

            return redirect()->route('shop.orders.show', ['token' => $order->access_token, 'payment' => 'unavailable']);
        }
    }
}
