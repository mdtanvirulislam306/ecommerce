<script setup>
import { useShopUi } from '@/Composables/useShopUi';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    product: { type: Object, required: true },
});

const { openProductModal, flyToCart } = useShopUi();

const formatMoney = (currency, amount) => {
    if (amount === null || amount === undefined) {
        return null;
    }
    return `${currency} ${Number(amount).toFixed(2)}`;
};

const openDetails = () => {
    openProductModal(props.product.slug);
};

const addClick = async (event) => {
    event.preventDefault();
    event.stopPropagation();

    if (props.product.has_variants || props.product.type === 'variant') {
        await openProductModal(props.product.slug);
        return;
    }

    if (!props.product.price || !props.product.in_stock) {
        return;
    }

    flyToCart(props.product.image_url, event);

    router.post(
        route('shop.cart.add'),
        {
            product_id: props.product.id,
            product_variant_id: null,
            quantity: 1,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['shopCart', 'cartCount', 'flash'],
        },
    );
};
</script>

<template>
    <article
        data-shop-fly-root
        class="group flex h-full cursor-pointer flex-col overflow-hidden rounded-2xl border border-brand-orange bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-brand-orange-dark hover:shadow-md"
        @click="openDetails"
    >
        <div class="relative aspect-square overflow-hidden bg-gray-50">
            <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                loading="lazy"
            />
            <div v-else class="flex h-full items-center justify-center text-xs text-gray-400">No image</div>
            <span
                v-if="product.is_featured"
                class="absolute left-2 top-2 rounded-full bg-brand-orange px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white"
            >
                Hot
            </span>
        </div>

        <div class="flex flex-1 flex-col gap-1.5 p-2.5 sm:p-3">
            <p class="line-clamp-2 min-h-[2.5rem] text-sm font-medium leading-snug text-brand-navy transition group-hover:text-brand-orange">
                {{ product.name }}
            </p>

            <div class="flex items-end justify-between gap-2">
                <p class="text-base font-semibold text-brand-navy">
                    <template v-if="product.price !== null">
                        {{ formatMoney(product.currency, product.price) }}
                    </template>
                    <template v-else>
                        <span class="text-xs font-normal text-gray-500">{{ product.price_message }}</span>
                    </template>
                </p>
                <p v-if="product.rating_count" class="shrink-0 text-xs text-gray-500">
                    <span class="font-medium text-brand-navy">{{ product.rating_avg }}</span>
                    <span>({{ product.rating_count }})</span>
                </p>
                <p v-else class="shrink-0 text-xs text-gray-400">New</p>
            </div>

            <button
                type="button"
                class="mt-auto inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-orange px-3 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-orange/25 transition hover:bg-brand-orange-dark active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400 disabled:shadow-none"
                :disabled="!product.has_variants && product.type !== 'variant' && (!product.price || !product.in_stock)"
                @click="addClick"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path
                        v-if="product.has_variants || product.type === 'variant'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4 6h16M4 12h16M4 18h10"
                    />
                    <path
                        v-else
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                {{ product.has_variants || product.type === 'variant' ? 'Choose options' : (product.in_stock ? 'Add to cart' : 'Out of stock') }}
            </button>
        </div>
    </article>
</template>
