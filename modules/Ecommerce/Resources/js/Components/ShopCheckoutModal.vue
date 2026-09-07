<script setup>
import { useShopUi } from '@/Composables/useShopUi';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const districts = [
    'Dhaka', 'Chattogram', 'Rajshahi', 'Khulna', 'Barishal', 'Sylhet', 'Rangpur', 'Mymensingh',
    'Gazipur', 'Narayanganj', 'Cumilla', 'Bogura', 'Jessore', 'Cox\'s Bazar',
];

const { state, closeCheckout, openCart } = useShopUi();
const page = usePage();
const cart = computed(() => page.props.shopCart ?? { items: [], subtotal: '0', currency: 'BDT', count: 0 });
const related = computed(() => {
    // lightweight suggestions from trending on page if available
    return page.props.trending?.slice?.(0, 3) || page.props.best_selling?.slice?.(0, 3) || [];
});

const form = useForm({
    customer_name: '',
    customer_phone: '',
    district: 'Dhaka',
    shipping_address: '',
    customer_email: '',
    payment_method: 'cod',
    notes: '',
});

const subtotal = computed(() => Number(cart.value.subtotal || 0));
const shipping = computed(() => (subtotal.value >= 2000 ? 0 : 60));
const total = computed(() => subtotal.value + shipping.value);

watch(
    () => state.checkoutOpen,
    (open) => {
        if (open) {
            document.body.classList.add('overflow-hidden');
        } else {
            document.body.classList.remove('overflow-hidden');
        }
    },
);

const submit = () => {
    const address = [form.shipping_address.trim(), form.district ? `District: ${form.district}` : '']
        .filter(Boolean)
        .join('\n');

    form.transform((data) => ({
        customer_name: data.customer_name,
        customer_phone: data.customer_phone || null,
        customer_email: data.customer_email || null,
        shipping_address: address,
        payment_method: data.payment_method,
        notes: data.notes || null,
    })).post(route('shop.checkout.store'), {
        onSuccess: () => {
            closeCheckout();
        },
    });
};

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
                        <button type="button" class="text-xs font-medium text-brand-orange hover:underline" @click="backToCart">← Cart</button>
                        <h2 class="text-lg font-semibold text-brand-navy">Checkout</h2>
                    </div>
                    <button type="button" class="rounded-full p-2 text-gray-500 hover:bg-gray-50" @click="closeCheckout">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form class="flex flex-1 flex-col overflow-hidden" @submit.prevent="submit">
                    <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-brand-navy">Delivery Information</h3>
                        <div class="space-y-3">
                            <div>
                                <InputLabel value="Full Name" />
                                <TextInput v-model="form.customer_name" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.customer_name" />
                            </div>
                            <div>
                                <InputLabel value="Phone Number" />
                                <TextInput v-model="form.customer_phone" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.customer_phone" />
                            </div>
                            <div>
                                <InputLabel value="District" />
                                <select v-model="form.district" class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand-teal focus:ring-brand-teal">
                                    <option v-for="d in districts" :key="d" :value="d">{{ d }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Address" />
                                <textarea
                                    v-model="form.shipping_address"
                                    rows="3"
                                    required
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand-teal focus:ring-brand-teal"
                                />
                                <InputError :message="form.errors.shipping_address" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-gray-50 px-4 py-3 text-sm">
                        <h3 class="mb-2 font-semibold text-brand-navy">Order Summary</h3>
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>{{ cart.currency }} {{ subtotal.toFixed(2) }}</span>
                        </div>
                        <div class="mt-1 flex justify-between text-gray-600">
                            <span>Delivery</span>
                            <span>{{ shipping === 0 ? 'FREE' : `${cart.currency} ${shipping.toFixed(2)}` }}</span>
                        </div>
                        <div class="mt-2 flex justify-between border-t border-gray-200 pt-2 font-semibold text-brand-navy">
                            <span>Total</span>
                            <span>{{ cart.currency }} {{ total.toFixed(2) }}</span>
                        </div>
                    </div>

                    <div v-if="related.length">
                        <h3 class="mb-2 text-sm font-semibold text-brand-navy">You May Also Like</h3>
                        <div class="grid grid-cols-3 gap-2">
                            <div v-for="item in related" :key="item.id" class="rounded-xl border border-gray-100 p-2 text-center">
                                <div class="aspect-square overflow-hidden rounded-lg bg-gray-50">
                                    <img v-if="item.image_url" :src="item.image_url" alt="" class="h-full w-full object-cover" />
                                </div>
                                <p class="mt-1 line-clamp-1 text-[11px] text-brand-navy">{{ item.name }}</p>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="border-t border-gray-100 px-5 py-4">
                        <button
                            type="submit"
                            class="w-full rounded-xl bg-brand-orange py-3.5 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark disabled:opacity-50"
                            :disabled="form.processing || !cart.items?.length"
                        >
                            Place Order · {{ cart.currency }} {{ total.toFixed(2) }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </Teleport>
</template>
