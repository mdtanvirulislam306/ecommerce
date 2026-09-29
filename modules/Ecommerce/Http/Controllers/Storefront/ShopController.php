<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Enums\PaymentMethod;
use Modules\Ecommerce\Http\Requests\AddToCartRequest;
use Modules\Ecommerce\Http\Requests\ApplyCouponRequest;
use Modules\Ecommerce\Http\Requests\CheckoutRequest;
use Modules\Ecommerce\Http\Requests\RemoveFromCartRequest;
use Modules\Ecommerce\Http\Requests\UpdateCartRequest;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Services\CartService;
use Modules\Ecommerce\Services\CustomerAccountService;
use Modules\Ecommerce\Services\OnlineOrderService;
use Modules\Ecommerce\Services\OnlinePaymentService;
use Modules\Ecommerce\Services\PaymentSettingService;
use Modules\Ecommerce\Services\StorefrontCatalogService;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ShopController extends Controller
{
    private const PAYMENT_NOTICES = ['success', 'failed', 'cancelled', 'unavailable', 'unconfirmed'];

    public function index(Request $request, StorefrontCatalogService $catalog): Response
    {
        $homepage = $catalog->homepage(
            $request->string('search')->trim()->toString() ?: null,
            $request->string('category')->trim()->toString() ?: null,
        );

        return Inertia::render('Ecommerce/Shop/Index', $homepage);
    }

    public function show(string $slug, StorefrontCatalogService $catalog): Response
    {
        return Inertia::render('Ecommerce/Shop/Show', [
            'product' => $catalog->productBySlug($slug),
        ]);
    }

    public function quick(string $slug, StorefrontCatalogService $catalog): JsonResponse
    {
        return response()->json($catalog->productBySlug($slug));
    }

    public function cart(CartService $cart): Response
    {
        return Inertia::render('Ecommerce/Shop/Cart', [
            'cart' => $cart->detailed(),
        ]);
    }

    public function add(AddToCartRequest $request, CartService $cart): RedirectResponse
    {
        $data = $request->validated();
        $cart->add(
            productId: (int) $data['product_id'],
            quantity: (float) $data['quantity'],
            productVariantId: $data['product_variant_id'] ?? null,
        );

        return back()->with('success', 'Added to cart.');
    }

    public function update(UpdateCartRequest $request, CartService $cart): RedirectResponse
    {
        $data = $request->validated();
        $cart->update(
            productId: (int) $data['product_id'],
            quantity: (float) $data['quantity'],
            productVariantId: $data['product_variant_id'] ?? null,
        );

        return back()->with('success', 'Cart updated.');
    }

    public function remove(RemoveFromCartRequest $request, CartService $cart): RedirectResponse
    {
        $data = $request->validated();
        $cart->remove(
            productId: (int) $data['product_id'],
            productVariantId: $data['product_variant_id'] ?? null,
        );

        return back()->with('success', 'Item removed.');
    }

    public function applyCoupon(ApplyCouponRequest $request, CartService $cart): RedirectResponse
    {
        $cart->applyCoupon($request->validated('coupon'));

        return back();
    }

    public function removeCoupon(CartService $cart): RedirectResponse
    {
        $cart->removeCoupon();

        return back();
    }

    public function checkoutForm(CartService $cart): Response|RedirectResponse
    {
        $detailed = $cart->detailed();

        if ($detailed['items'] === []) {
            return redirect()->route('shop.index')->with('success', 'Your cart is empty.');
        }

        return Inertia::render('Ecommerce/Shop/Checkout', [
            'cart' => $detailed,
        ]);
    }

    public function checkout(
        CheckoutRequest $request,
        OnlineOrderService $orders,
        OnlinePaymentService $payments,
        PaymentSettingService $settings,
        CustomerAccountService $accounts,
    ): SymfonyResponse {
        $account = $request->user('customer');

        if ($account === null && ! $settings->guestCheckoutAllowed()) {
            $request->session()->put(CustomerAuthController::INTENDED_KEY, route('shop.checkout'));

            return redirect()
                ->route('shop.account.login')
                ->with('status', 'Please sign in or create an account to place your order.');
        }

        $data = $request->validated();
        $order = $orders->checkoutFromCart($data, $account);

        if ($account !== null) {
            $accounts->rememberAddress($account, $data['address_line'] ?? null, $data['district'] ?? null, $data['customer_phone'] ?? null);
        }

        if ($order->payment_method === PaymentMethod::Online) {
            return OrderPaymentController::redirectToGateway($order, $payments);
        }

        return redirect()
            ->route('shop.index')
            ->with('order_placed', $orders->formatDetail($order));
    }

    /**
     * Guests have no account, so the unguessable token in their order link is what proves the order is theirs.
     */
    public function order(Request $request, string $token, OnlineOrderService $orders, OnlinePaymentService $payments): Response
    {
        $order = OnlineOrder::query()->where('access_token', $token)->firstOrFail();
        $notice = $request->query('payment');

        $account = $request->user('customer');

        return Inertia::render('Ecommerce/Shop/Order', [
            'order' => $orders->formatDetail($order),
            'ownsOrder' => $account !== null && $order->customer_account_id === $account->id,
            'payment' => [
                'can_pay_online' => $payments->canPayOnline($order),
                'can_pay_on_delivery' => $payments->canSwitchToCashOnDelivery($order),
                'pay_url' => route('shop.orders.pay', $order->access_token),
                'cash_on_delivery_url' => route('shop.orders.cash-on-delivery', $order->access_token),
                'notice' => in_array($notice, self::PAYMENT_NOTICES, true) ? $notice : null,
            ],
        ]);
    }
}
