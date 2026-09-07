<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\AddToCartRequest;
use Modules\Ecommerce\Http\Requests\CheckoutRequest;
use Modules\Ecommerce\Http\Requests\RemoveFromCartRequest;
use Modules\Ecommerce\Http\Requests\UpdateCartRequest;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Services\CartService;
use Modules\Ecommerce\Services\OnlineOrderService;
use Modules\Ecommerce\Services\StorefrontCatalogService;

class ShopController extends Controller
{
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

    public function checkout(CheckoutRequest $request, OnlineOrderService $orders): RedirectResponse
    {
        $order = $orders->checkoutFromCart($request->validated());

        return redirect()
            ->route('shop.thanks', $order)
            ->with('success', 'Order placed. Pay on delivery.');
    }

    public function thanks(OnlineOrder $onlineOrder, OnlineOrderService $orders): Response
    {
        return Inertia::render('Ecommerce/Shop/Thanks', [
            'order' => $orders->formatDetail($onlineOrder),
        ]);
    }
}
