<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    products: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const addToCart = (product) => {
    router.post(
        route('shop.cart.add'),
        { product_id: product.id, quantity: 1 },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Shop" />

    <StorefrontLayout>
        <div v-if="flash?.success" class="mb-4 text-sm text-emerald-700">{{ flash.success }}</div>

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
                class="flex flex-col overflow-hidden border border-black/5 bg-white"
            >
                <Link :href="route('shop.products.show', product.slug)" class="block aspect-[4/3] bg-gray-50">
                    <img
                        v-if="product.image_url"
                        :src="product.image_url"
                        :alt="product.name"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                    <div v-else class="flex h-full items-center justify-center text-xs text-gray-400">No image</div>
                </Link>
                <div class="flex flex-1 flex-col p-5">
                    <div class="flex items-start justify-between gap-2">
                        <Link :href="route('shop.products.show', product.slug)" class="text-base font-medium hover:text-brand-orange">
                            {{ product.name }}
                        </Link>
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
                    <div class="mt-auto flex items-center justify-between gap-3 pt-5">
                        <Link
                            :href="route('shop.products.show', product.slug)"
                            class="text-sm font-medium text-brand-navy underline-offset-4 hover:underline"
                        >
                            View
                        </Link>
                        <button
                            v-if="product.type !== 'variant'"
                            type="button"
                            class="text-sm font-medium text-brand-navy underline-offset-4 hover:underline disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="!product.price || !product.in_stock"
                            @click="addToCart(product)"
                        >
                            Add to cart
                        </button>
                    </div>
                </div>
            </article>
        </div>

        <p v-if="!products.length" class="py-16 text-center text-sm text-gray-500">
            No published products yet. Publish from Admin → Ecommerce → Online Products.
        </p>
    </StorefrontLayout>
</template>
