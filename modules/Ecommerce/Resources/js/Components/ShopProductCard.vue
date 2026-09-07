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
        class="group flex h-full cursor-pointer flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-brand-teal/30 hover:shadow-md"
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

        <div class="flex flex-1 flex-col gap-1 p-2 sm:p-2.5">
            <p class="line-clamp-2 min-h-[2.25rem] text-xs font-medium text-brand-navy transition group-hover:text-brand-orange sm:text-sm">
                {{ product.name }}
            </p>

            <p class="text-sm font-semibold text-brand-navy">
                <template v-if="product.price !== null">
                    {{ formatMoney(product.currency, product.price) }}
                </template>
                <template v-else>
                    <span class="text-xs font-normal text-gray-500">{{ product.price_message }}</span>
                </template>
            </p>

            <div class="mt-auto flex items-center justify-between gap-2 pt-1">
                <p v-if="product.rating_count" class="flex items-center gap-1 text-xs text-gray-500">
                    <span class="font-medium text-brand-navy">{{ product.rating_avg }}</span>
                    <span>({{ product.rating_count }})</span>
                </p>
                <p v-else class="text-xs text-gray-400">New</p>
                <button
                    type="button"
                    class="rounded-lg bg-brand-orange/10 px-2.5 py-1 text-[11px] font-semibold text-brand-orange transition hover:bg-brand-orange hover:text-white"
                    @click="addClick"
                >
                    {{ product.has_variants ? 'Options' : 'Add' }}
                </button>
            </div>
        </div>
    </article>
</template>
