<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    order: { type: Object, required: true },
});
</script>

<template>
    <Head :title="`Order ${order.number}`" />

    <StorefrontLayout>
        <div class="mx-auto max-w-lg border border-black/5 bg-white p-8 text-center">
            <p class="text-sm uppercase tracking-wide text-brand-orange">Thank you</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Order placed</h1>
            <p class="mt-3 text-sm text-gray-600">
                {{ order.number }} · {{ order.payment_method_label }} ·
                {{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}
            </p>
            <p class="mt-2 text-sm text-gray-500">We’ll confirm and ship soon. Pay when you receive the goods.</p>
            <Link :href="route('shop.index')" class="mt-8 inline-block text-sm font-medium underline-offset-4 hover:underline">
                Back to shop
            </Link>
        </div>
    </StorefrontLayout>
</template>
