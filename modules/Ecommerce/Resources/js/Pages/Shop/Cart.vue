<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    cart: { type: Object, required: true },
});

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
</script>

<template>
    <Head title="Cart" />

    <StorefrontLayout>
        <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Cart</h1>
                <p class="mt-1 text-sm text-gray-600">{{ cart.count }} item(s)</p>
            </div>
            <Link :href="route('shop.index')" class="text-sm hover:text-brand-orange">Continue shopping</Link>
        </div>

        <div v-if="cart.items.length" class="space-y-4">
            <div
                v-for="item in cart.items"
                :key="`${item.product_id}-${item.product_variant_id || 0}`"
                class="flex flex-col gap-3 border border-black/5 bg-white p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="font-medium">{{ item.name }}</p>
                    <p class="text-xs text-gray-500">{{ item.sku }}</p>
                    <p class="mt-1 text-sm">
                        {{ item.currency }} {{ Number(item.unit_price).toFixed(2) }}
                    </p>
                    <p v-if="!item.can_fulfill" class="mt-1 text-xs text-red-600">Not enough stock</p>
                </div>
                <div class="flex items-center gap-3">
                    <input
                        type="number"
                        min="0"
                        step="1"
                        class="w-20 rounded border-gray-200 text-sm"
                        :value="Number(item.quantity)"
                        @change="updateQty(item, Number($event.target.value))"
                    />
                    <p class="w-24 text-right text-sm font-medium">
                        {{ item.currency }} {{ Number(item.line_total).toFixed(2) }}
                    </p>
                    <button type="button" class="text-xs text-red-600 hover:underline" @click="removeItem(item)">
                        Remove
                    </button>
                </div>
            </div>

            <div class="flex flex-col items-end gap-3 border-t border-black/5 pt-4">
                <p class="text-lg font-semibold">
                    Subtotal: {{ cart.currency }} {{ Number(cart.subtotal).toFixed(2) }}
                </p>
                <Link
                    :href="route('shop.checkout')"
                    class="bg-brand-navy px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-navy/90"
                >
                    Checkout
                </Link>
            </div>
        </div>

        <p v-else class="py-16 text-center text-sm text-gray-500">Your cart is empty.</p>
        </div>
    </StorefrontLayout>
</template>
