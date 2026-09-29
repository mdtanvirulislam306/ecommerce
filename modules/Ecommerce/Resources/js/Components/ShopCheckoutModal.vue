<script setup>
import ShopCheckoutForm from './ShopCheckoutForm.vue';
import { useShopUi } from '@/composables/useShopUi';
import { watch } from 'vue';

const { state, closeCheckout, openCart } = useShopUi();

watch(
    () => state.checkoutOpen,
    (open) => {
        document.body.classList.toggle('overflow-hidden', open);
    },
);

const backToCart = () => {
    closeCheckout();
    openCart();
};
</script>

<template>
    <Teleport to="body">
        <div v-if="state.checkoutOpen" class="fixed inset-0 z-[75]">
            <button type="button" class="absolute inset-0 bg-brand-navy/40" aria-label="Close checkout" @click="closeCheckout" />
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl sm:max-w-lg">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <button type="button" class="text-xs font-medium text-brand-orange hover:underline" @click="backToCart">← Back to cart</button>
                        <h2 class="text-lg font-semibold text-brand-navy">Checkout</h2>
                    </div>
                    <button type="button" class="rounded-full p-2 text-gray-500 hover:bg-gray-50" aria-label="Close" @click="closeCheckout">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <ShopCheckoutForm layout="drawer" @placed="closeCheckout" />
            </aside>
        </div>
    </Teleport>
</template>
