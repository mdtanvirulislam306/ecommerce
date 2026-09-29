<script setup>
import ShopCheckoutForm from '../../Components/ShopCheckoutForm.vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Checkout" />

    <StorefrontLayout>
        <div class="w-full bg-gray-50/60">
            <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <Link :href="route('shop.cart')" class="text-sm text-gray-500 hover:text-brand-orange">← Back to cart</Link>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-brand-navy">Checkout</h1>
                </div>

                <ShopCheckoutForm layout="page" />
            </div>
        </div>
    </StorefrontLayout>
</template>
