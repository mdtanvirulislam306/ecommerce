<script setup>
import ShopCouponField from './ShopCouponField.vue';
import { useCheckoutTotals } from '@/composables/useCheckoutTotals';
import { useShopUi } from '@/composables/useShopUi';
import { formatMoney } from '@/utils/formatMoney';
import { router, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const { state, closeCart, openCheckout } = useShopUi();
const page = usePage();

const cart = computed(() => page.props.shopCart ?? { items: [], subtotal: '0', discount: '0', currency: 'BDT', count: 0 });
const money = (amount) => formatMoney(amount, cart.value.currency || 'BDT');
const { delivery, subtotal, discount, freeThreshold, qualifiesForFree, freeRemaining, freeProgress, cheapestRate, total } = useCheckoutTotals(cart);

const deliveryLabel = computed(() => {
    if (!delivery.value.enabled || qualifiesForFree.value) {
        return 'FREE';
    }
    return delivery.value.zones.length > 1 ? `From ${money(cheapestRate.value)}` : money(cheapestRate.value);
});

watch(
    () => state.cartOpen,
    (open) => {
        document.body.classList.toggle('overflow-hidden', open || state.checkoutOpen || !!state.product || !!state.variantProduct);
    },
);

const updateQty = (item, quantity) => {
    router.put(
        route('shop.cart.update'),
        {
            product_id: item.product_id,
            product_variant_id: item.product_variant_id,
            quantity,
        },
        { preserveScroll: true },
    );
};

const removeItem = (item) => {
    router.delete(route('shop.cart.remove'), {
        data: {
            product_id: item.product_id,
            product_variant_id: item.product_variant_id,
            quantity: 0,
        },
        preserveScroll: true,
    });
};

const goCheckout = () => {
    if (!cart.value.items?.length) {
        return;
    }
    openCheckout();
};
</script>

<template>
    <Teleport to="body">
        <div v-if="state.cartOpen" class="fixed inset-0 z-[70]">
            <button type="button" class="absolute inset-0 bg-brand-navy/40" aria-label="Close cart" @click="closeCart" />
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-2xl sm:max-w-md">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h2 class="text-lg font-semibold text-brand-navy">My Cart ({{ cart.count || 0 }})</h2>
                    <button type="button" class="rounded-full p-2 text-gray-500 hover:bg-gray-50" aria-label="Close" @click="closeCart">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <div v-if="cart.items?.length && freeThreshold !== null" class="rounded-xl px-3 py-3" :class="qualifiesForFree ? 'bg-emerald-50' : 'bg-brand-orange/10'">
                        <p class="text-xs font-medium" :class="qualifiesForFree ? 'text-emerald-800' : 'text-brand-navy'">
                            <template v-if="!qualifiesForFree">
                                Add <span class="font-semibold">{{ money(freeRemaining) }}</span> more to get FREE delivery
                            </template>
                            <template v-else>You unlocked FREE delivery!</template>
                        </p>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-white">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="qualifiesForFree ? 'bg-emerald-500' : 'bg-brand-orange'"
                                :style="{ width: `${freeProgress}%` }"
                            />
                        </div>
                    </div>

                    <div v-for="item in cart.items" :key="`${item.product_id}-${item.product_variant_id || 0}`" class="flex gap-3 border-b border-gray-50 pb-4">
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-gray-50">
                            <img v-if="item.image_url" :src="item.image_url" alt="" class="h-full w-full object-cover" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-brand-navy">{{ item.name }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ item.sku }}</p>
                            <p class="mt-1 text-sm font-semibold text-brand-navy">
                                {{ money(item.unit_price) }}
                            </p>
                            <div class="mt-2 flex items-center gap-2">
                                <button type="button" class="h-7 w-7 rounded-lg border border-gray-200 text-sm" @click="updateQty(item, Math.max(1, Number(item.quantity) - 1))">−</button>
                                <span class="w-6 text-center text-sm">{{ Number(item.quantity).toFixed(0) }}</span>
                                <button type="button" class="h-7 w-7 rounded-lg border border-gray-200 text-sm" @click="updateQty(item, Number(item.quantity) + 1)">+</button>
                            </div>
                        </div>
                        <button type="button" class="self-start text-gray-400 hover:text-red-500" @click="removeItem(item)">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8" />
                            </svg>
                        </button>
                    </div>

                    <p v-if="!cart.items?.length" class="py-16 text-center text-sm text-gray-500">Your cart is empty.</p>

                    <div v-if="cart.items?.length" class="pt-1">
                        <ShopCouponField />
                    </div>
                </div>

                <div v-if="cart.items?.length" class="border-t border-gray-100 px-5 py-4">
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>{{ money(subtotal) }}</span>
                        </div>
                        <div v-if="discount > 0" class="flex justify-between text-emerald-600">
                            <span>Coupon discount</span>
                            <span>−{{ money(discount) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Delivery</span>
                            <span :class="deliveryLabel === 'FREE' ? 'font-medium text-emerald-600' : ''">{{ deliveryLabel }}</span>
                        </div>
                        <div class="flex justify-between pt-2 text-base font-semibold text-brand-navy">
                            <span>{{ delivery.enabled && !qualifiesForFree ? 'Total (before delivery)' : 'Total' }}</span>
                            <span>{{ money(total) }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-4 w-full rounded-xl bg-brand-orange py-3 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark"
                        @click="goCheckout"
                    >
                        Checkout
                    </button>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
