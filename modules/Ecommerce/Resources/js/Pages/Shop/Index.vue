<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    products: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const addToCart = (productId) => {
    router.post(route('shop.cart.add'), { product_id: productId, quantity: 1 }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Shop" />

    <StorefrontLayout>
        <div class="mb-8">
            <h1 class="text-3xl font-semibold tracking-tight">Shop</h1>
            <p class="mt-2 max-w-xl text-sm text-gray-600">
                Published products with live prices from Commerce and stock from Inventory. Checkout is cash on delivery.
            </p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="product in products"
                :key="product.id"
                class="flex flex-col border border-black/5 bg-white p-5"
            >
                <div class="flex items-start justify-between gap-2">
                    <h2 class="text-base font-medium">{{ product.name }}</h2>
                    <span
                        v-if="product.is_featured"
                        class="text-[11px] uppercase tracking-wide text-brand-orange"
                    >
                        Featured
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500">{{ product.sku || 'No SKU' }}</p>
                <p class="mt-4 text-lg font-semibold">
                    <template v-if="product.price">
                        {{ product.currency }} {{ Number(product.price).toFixed(2) }}
                    </template>
                    <template v-else>
                        <span class="text-sm font-normal text-gray-500">{{ product.price_message }}</span>
                    </template>
                </p>
                <p class="mt-1 text-xs" :class="product.in_stock ? 'text-emerald-700' : 'text-red-600'">
                    {{ product.in_stock ? `In stock (${Number(product.stock_available).toFixed(0)})` : 'Out of stock' }}
                </p>
                <button
                    type="button"
                    class="mt-auto pt-5 text-left text-sm font-medium text-brand-navy underline-offset-4 hover:underline disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="!product.price || !product.in_stock"
                    @click="addToCart(product.id)"
                >
                    Add to cart
                </button>
            </article>
        </div>

        <p v-if="!products.length" class="py-16 text-center text-sm text-gray-500">
            No published products yet. Publish from Admin → Ecommerce → Online Products.
        </p>
    </StorefrontLayout>
</template>
