<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    cart: { type: Object, required: true },
});

const form = useForm({
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    shipping_address: '',
    payment_method: 'cod',
    notes: '',
});

const submit = () => {
    form.post(route('shop.checkout.store'));
};
</script>

<template>
    <Head title="Checkout" />

    <StorefrontLayout>
        <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <Link :href="route('shop.cart')" class="text-sm hover:text-brand-orange">← Cart</Link>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Checkout</h1>
            <p class="mt-1 text-sm text-gray-600">One-page checkout · Cash on delivery</p>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
            <form class="space-y-4 border border-black/5 bg-white p-5" @submit.prevent="submit">
                <div>
                    <InputLabel value="Full name" />
                    <TextInput v-model="form.customer_name" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.customer_name" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Email" />
                        <TextInput v-model="form.customer_email" type="email" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Phone" />
                        <TextInput v-model="form.customer_phone" class="mt-1 block w-full" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Shipping address" />
                    <textarea
                        v-model="form.shipping_address"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                        required
                    />
                    <InputError class="mt-1" :message="form.errors.shipping_address" />
                </div>
                <div>
                    <InputLabel value="Notes" />
                    <TextInput v-model="form.notes" class="mt-1 block w-full" />
                </div>
                <p class="text-sm text-gray-600">Payment: Cash on delivery</p>
                <InputError :message="form.errors.cart" />
                <button
                    type="submit"
                    class="bg-brand-navy px-5 py-2.5 text-sm font-medium text-white disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Place order
                </button>
            </form>

            <aside class="border border-black/5 bg-white p-5">
                <h2 class="text-sm font-semibold">Order summary</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li v-for="item in cart.items" :key="`${item.product_id}-${item.product_variant_id || 0}`" class="flex justify-between gap-3">
                        <span>{{ item.name }} × {{ Number(item.quantity).toFixed(0) }}</span>
                        <span>{{ item.currency }} {{ Number(item.line_total).toFixed(2) }}</span>
                    </li>
                </ul>
                <p class="mt-5 border-t border-black/5 pt-4 text-base font-semibold">
                    Total {{ cart.currency }} {{ Number(cart.subtotal).toFixed(2) }}
                </p>
            </aside>
        </div>
        </div>
    </StorefrontLayout>
</template>
