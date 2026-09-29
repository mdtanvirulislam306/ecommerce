<?php

namespace Modules\Ecommerce\Services;

use App\Core\Contracts\PriceResolver;
use App\Core\Contracts\StockAvailability;
use App\Core\Support\Service;
use App\Core\Tenant\TenantContext;
use App\Core\Tenant\TenantQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartService extends Service
{
    private const SESSION_KEY = 'ecommerce_cart';

    public function __construct(
        private readonly PriceResolver $prices,
        private readonly StockAvailability $stock,
        private readonly StorefrontCouponService $coupons,
        private readonly DeliveryRateService $delivery,
        private readonly PaymentSettingService $payments,
    ) {}

    private function sessionKey(): string
    {
        $tenantId = app(TenantContext::class)->id();

        return self::SESSION_KEY.($tenantId ? '_t'.$tenantId : '');
    }

    /**
     * @return list<array{product_id: int, product_variant_id: ?int, quantity: float}>
     */
    public function raw(): array
    {
        return collect(Session::get($this->sessionKey(), []))
            ->map(fn ($line) => [
                'product_id' => (int) ($line['product_id'] ?? 0),
                'product_variant_id' => ! empty($line['product_variant_id']) ? (int) $line['product_variant_id'] : null,
                'quantity' => (float) ($line['quantity'] ?? 0),
            ])
            ->filter(fn ($line) => $line['product_id'] > 0 && $line['quantity'] > 0)
            ->values()
            ->all();
    }

    public function count(): int
    {
        return (int) collect($this->raw())->sum('quantity');
    }

    public function clear(): void
    {
        Session::forget([$this->sessionKey(), $this->couponSessionKey()]);
    }

    public function add(int $productId, float $quantity = 1, ?int $productVariantId = null): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
        }

        $this->assertPublished($productId);
        $lines = $this->raw();
        $found = false;

        foreach ($lines as &$line) {
            if ($line['product_id'] === $productId && $line['product_variant_id'] === $productVariantId) {
                $line['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        unset($line);

        if (! $found) {
            $lines[] = [
                'product_id' => $productId,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
            ];
        }

        Session::put($this->sessionKey(), $lines);
    }

    public function update(int $productId, float $quantity, ?int $productVariantId = null): void
    {
        if ($quantity <= 0) {
            $this->remove($productId, $productVariantId);

            return;
        }

        $lines = $this->raw();
        $updated = false;

        foreach ($lines as &$line) {
            if ($line['product_id'] === $productId && $line['product_variant_id'] === $productVariantId) {
                $line['quantity'] = $quantity;
                $updated = true;
                break;
            }
        }
        unset($line);

        if (! $updated) {
            throw ValidationException::withMessages(['cart' => 'Cart line not found.']);
        }

        Session::put($this->sessionKey(), $lines);
    }

    public function remove(int $productId, ?int $productVariantId = null): void
    {
        $lines = collect($this->raw())
            ->reject(fn ($line) => $line['product_id'] === $productId && $line['product_variant_id'] === $productVariantId)
            ->values()
            ->all();

        Session::put($this->sessionKey(), $lines);
    }

    /**
     * @return array{items: list<array<string, mixed>>, subtotal: string, discount: string, coupon: array<string, mixed>|null, currency: string, count: int, delivery: array<string, mixed>, payment: array{options: list<array<string, mixed>>, requires_phone: bool, guest_checkout: bool}}
     */
    public function detailed(): array
    {
        ['items' => $items, 'subtotal' => $subtotal, 'currency' => $currency] = $this->pricedLines();
        $coupon = $this->appliedCoupon($subtotal);

        return [
            'items' => $items,
            'subtotal' => number_format($subtotal, 4, '.', ''),
            'discount' => number_format($coupon['discount'] ?? 0, 4, '.', ''),
            'coupon' => $coupon,
            'currency' => $currency,
            'count' => (int) collect($items)->sum(fn ($i) => (float) $i['quantity']),
            'delivery' => $this->delivery->options(),
            'payment' => [
                'options' => $this->payments->checkoutOptions(),
                'requires_phone' => $this->payments->requiresPhone(),
                'guest_checkout' => $this->payments->guestCheckoutAllowed(),
            ],
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, subtotal: float, currency: string}
     */
    private function pricedLines(): array
    {
        $items = [];
        $subtotal = 0.0;
        $currency = 'BDT';

        foreach ($this->raw() as $line) {
            $product = TenantQuery::constrain(DB::table('products'), 'products')
                ->where('id', $line['product_id'])
                ->first();

            if ($product === null || $product->publication_status !== 'published' || $product->status === 'archived') {
                continue;
            }

            $sku = $product->sku;
            $name = $product->name;
            $variantId = $line['product_variant_id'];

            if ($variantId) {
                $variant = DB::table('product_variants')
                    ->where('id', $variantId)
                    ->where('product_id', $product->id)
                    ->first();

                if ($variant === null) {
                    continue;
                }

                $sku = $variant->sku;
                $name = trim($product->name.($variant->name ? ' — '.$variant->name : ' — '.$variant->sku));
            }

            $resolved = $this->prices->resolve(
                productId: $product->id,
                quantity: (int) max(1, floor($line['quantity'])),
                productVariantId: $variantId,
            );

            if (! $resolved['resolved'] || $resolved['price'] === null) {
                continue;
            }

            $unit = (float) $resolved['price'];
            $lineTotal = $unit * $line['quantity'];
            $subtotal += $lineTotal;
            $currency = $resolved['currency'] ?? $currency;
            $available = $this->stock->available($product->id, $variantId);

            $imagePath = null;
            if ($variantId) {
                $imagePath = DB::table('product_media')
                    ->where('product_variant_id', $variantId)
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order')
                    ->value('path');
            }
            if (! $imagePath) {
                $imagePath = DB::table('product_media')
                    ->where('product_id', $product->id)
                    ->whereNull('product_variant_id')
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order')
                    ->value('path');
            }

            $items[] = [
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
                'sku' => $sku,
                'name' => $name,
                'image_url' => $imagePath
                    ? '/storage/'.ltrim(str_replace('\\', '/', $imagePath), '/')
                    : null,
                'quantity' => number_format($line['quantity'], 4, '.', ''),
                'unit_price' => number_format($unit, 4, '.', ''),
                'line_total' => number_format($lineTotal, 4, '.', ''),
                'currency' => $currency,
                'stock_available' => $available['available'],
                'can_fulfill' => $this->stock->canFulfill($product->id, $line['quantity'], $variantId),
            ];
        }

        return ['items' => $items, 'subtotal' => $subtotal, 'currency' => $currency];
    }

    public function couponCode(): ?string
    {
        return Session::get($this->couponSessionKey());
    }

    /**
     * @throws ValidationException when the code cannot be used on the current cart
     */
    public function applyCoupon(string $code): void
    {
        $subtotal = $this->pricedLines()['subtotal'];

        if ($subtotal <= 0) {
            throw ValidationException::withMessages(['coupon' => 'Add something to your cart before applying a coupon.']);
        }

        $applied = $this->coupons->resolve($code, $subtotal);

        Session::put($this->couponSessionKey(), $applied['coupon']->code);
    }

    public function removeCoupon(): void
    {
        Session::forget($this->couponSessionKey());
    }

    /**
     * A coupon that stopped being valid (expired, used up) is reported instead of silently discounting.
     *
     * @return array{code: string, name: string, discount: float}|array{code: string, error: string}|null
     */
    private function appliedCoupon(float $subtotal): ?array
    {
        $code = $this->couponCode();

        if ($code === null || $subtotal <= 0) {
            return null;
        }

        try {
            $applied = $this->coupons->resolve($code, $subtotal);
        } catch (ValidationException $exception) {
            return ['code' => $code, 'error' => $exception->errors()['coupon'][0] ?? 'This coupon can no longer be used.'];
        }

        return [
            'code' => $applied['coupon']->code,
            'name' => $applied['coupon']->name,
            'discount' => $applied['discount'],
        ];
    }

    private function couponSessionKey(): string
    {
        return $this->sessionKey().'_coupon';
    }

    private function assertPublished(int $productId): void
    {
        $product = DB::table('products')->where('id', $productId)->first();

        if ($product === null || $product->publication_status !== 'published' || $product->status === 'archived') {
            throw ValidationException::withMessages([
                'product_id' => 'Product is not available on the storefront.',
            ]);
        }
    }
}
