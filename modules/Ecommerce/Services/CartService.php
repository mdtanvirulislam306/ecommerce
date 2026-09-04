<?php

namespace Modules\Ecommerce\Services;

use App\Core\Contracts\PriceResolver;
use App\Core\Contracts\StockAvailability;
use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartService extends Service
{
    private const SESSION_KEY = 'ecommerce_cart';

    public function __construct(
        private readonly PriceResolver $prices,
        private readonly StockAvailability $stock,
    ) {}

    /**
     * @return list<array{product_id: int, product_variant_id: ?int, quantity: float}>
     */
    public function raw(): array
    {
        return collect(Session::get(self::SESSION_KEY, []))
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
        Session::forget(self::SESSION_KEY);
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

        Session::put(self::SESSION_KEY, $lines);
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

        Session::put(self::SESSION_KEY, $lines);
    }

    public function remove(int $productId, ?int $productVariantId = null): void
    {
        $lines = collect($this->raw())
            ->reject(fn ($line) => $line['product_id'] === $productId && $line['product_variant_id'] === $productVariantId)
            ->values()
            ->all();

        Session::put(self::SESSION_KEY, $lines);
    }

    /**
     * @return array{items: list<array<string, mixed>>, subtotal: string, currency: string, count: int}
     */
    public function detailed(): array
    {
        $items = [];
        $subtotal = 0.0;
        $currency = 'BDT';

        foreach ($this->raw() as $line) {
            $product = DB::table('products')->where('id', $line['product_id'])->first();

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

            $items[] = [
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
                'sku' => $sku,
                'name' => $name,
                'quantity' => number_format($line['quantity'], 4, '.', ''),
                'unit_price' => number_format($unit, 4, '.', ''),
                'line_total' => number_format($lineTotal, 4, '.', ''),
                'currency' => $currency,
                'stock_available' => $available['available'],
                'can_fulfill' => $this->stock->canFulfill($product->id, $line['quantity'], $variantId),
            ];
        }

        return [
            'items' => $items,
            'subtotal' => number_format($subtotal, 4, '.', ''),
            'currency' => $currency,
            'count' => (int) collect($items)->sum(fn ($i) => (float) $i['quantity']),
        ];
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
