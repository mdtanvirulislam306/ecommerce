<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Core\Tenant\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Ecommerce\Enums\PaymentStatus;
use Modules\Ecommerce\Exceptions\PaymentGatewayException;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Services\OnlinePaymentService;

/**
 * SSLCommerz posts here from its own domain, so these routes run without session or CSRF middleware.
 */
class SslCommerzCallbackController extends Controller
{
    public function __construct(private readonly TenantContext $tenants) {}

    public function success(Request $request, OnlinePaymentService $payments): RedirectResponse
    {
        $this->ensureShopResolved();

        $order = $this->confirm($request, $payments);

        if ($order?->payment_status === PaymentStatus::Paid) {
            return $this->toOrder($order->access_token, 'success');
        }

        return $this->toOrder($request->input('value_a'), 'unconfirmed');
    }

    public function fail(Request $request, OnlinePaymentService $payments): RedirectResponse
    {
        $this->ensureShopResolved();

        $payments->markFailed($request->input('value_a'), $request->input('tran_id'));

        return $this->toOrder($request->input('value_a'), 'failed');
    }

    public function cancel(Request $request, OnlinePaymentService $payments): RedirectResponse
    {
        $this->ensureShopResolved();

        $payments->markFailed($request->input('value_a'), $request->input('tran_id'));

        return $this->toOrder($request->input('value_a'), 'cancelled');
    }

    public function ipn(Request $request, OnlinePaymentService $payments): Response
    {
        $this->ensureShopResolved();

        if (in_array($request->input('status'), ['FAILED', 'CANCELLED', 'UNATTEMPTED', 'EXPIRED'], true)) {
            $payments->markFailed($request->input('value_a'), $request->input('tran_id'));
        } else {
            $this->confirm($request, $payments);
        }

        return response('OK');
    }

    private function confirm(Request $request, OnlinePaymentService $payments): ?OnlineOrder
    {
        try {
            return $payments->confirmPayment($request->input('val_id'));
        } catch (PaymentGatewayException $exception) {
            report($exception);

            return null;
        }
    }

    private function toOrder(mixed $accessToken, string $notice): RedirectResponse
    {
        if (! is_string($accessToken) || ! preg_match('/^[A-Za-z0-9]{40}$/', $accessToken)) {
            return redirect()->route('shop.index');
        }

        return redirect()->route('shop.orders.show', ['token' => $accessToken, 'payment' => $notice]);
    }

    private function ensureShopResolved(): void
    {
        abort_unless($this->tenants->check(), 404);
    }
}
