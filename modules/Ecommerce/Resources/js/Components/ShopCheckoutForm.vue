<script setup>
import { districts } from './districts';
import ShopCouponField from './ShopCouponField.vue';
import InputError from '@/Components/InputError.vue';
import { useCheckoutTotals } from '@/composables/useCheckoutTotals';
import { formatMoney } from '@/utils/formatMoney';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    layout: { type: String, default: 'drawer' },
});

const emit = defineEmits(['placed']);

const page = usePage();
const cart = computed(() => page.props.shopCart ?? { items: [], subtotal: '0', discount: '0', currency: 'BDT', count: 0 });
const currency = computed(() => cart.value.currency || 'BDT');
const money = (amount) => formatMoney(amount, currency.value);

const paymentOptions = computed(() => cart.value.payment?.options ?? []);
const shopCustomer = computed(() => page.props.shopCustomer ?? null);
const mustSignIn = computed(() => !shopCustomer.value && cart.value.payment?.guest_checkout === false);
const signInUrl = computed(() => route('shop.account.login', { redirect: 'checkout' }));
const registerUrl = computed(() => route('shop.account.register', { redirect: 'checkout' }));

const form = useForm({
    customer_name: shopCustomer.value?.name ?? '',
    customer_phone: shopCustomer.value?.phone ?? '',
    customer_email: shopCustomer.value?.email ?? '',
    delivery_zone: null,
    district: shopCustomer.value?.default_district ?? 'Dhaka',
    shipping_address: shopCustomer.value?.default_address ?? '',
    payment_method: paymentOptions.value[0]?.code ?? 'cod',
    notes: '',
});

const showNote = ref(false);
const zoneCode = computed(() => form.delivery_zone);
const totals = useCheckoutTotals(cart, zoneCode);

const isOptionAvailable = (option) => !option.minimum || totals.total.value >= option.minimum;

watch(
    [paymentOptions, () => totals.total.value],
    () => {
        const selected = paymentOptions.value.find((option) => option.code === form.payment_method);
        if (!selected || !isOptionAvailable(selected)) {
            form.payment_method = paymentOptions.value.find(isOptionAvailable)?.code ?? form.payment_method;
        }
    },
    { immediate: true },
);

const paysOnline = computed(() => form.payment_method === 'online');
const phoneRequired = computed(() => Boolean(cart.value.payment?.requires_phone) || paysOnline.value);
const walletBrands = ['bKash', 'Nagad', 'Rocket', 'Card'];

watch(
    () => totals.delivery.value.zones,
    (zones) => {
        if (zones.length === 1) {
            form.delivery_zone = zones[0].code;
        } else if (!zones.some((zone) => zone.code === form.delivery_zone)) {
            form.delivery_zone = null;
        }
    },
    { immediate: true },
);

const generalError = computed(() => form.errors.cart || form.errors.stock || form.errors.payment || null);
const canSubmit = computed(() => cart.value.items?.length && paymentOptions.value.length && !form.processing && !mustSignIn.value);

const submit = () => {
    const addressLine = form.shipping_address.trim();
    const address = [addressLine, form.district ? `District: ${form.district}` : '']
        .filter(Boolean)
        .join('\n');

    form.transform((data) => ({
        customer_name: data.customer_name,
        customer_phone: data.customer_phone || null,
        customer_email: data.customer_email || null,
        delivery_zone: data.delivery_zone,
        shipping_address: address,
        address_line: addressLine || null,
        district: data.district || null,
        payment_method: data.payment_method,
        notes: data.notes || null,
    })).post(route('shop.checkout.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('placed');
        },
    });
};

const inputClass = 'mt-1 block w-full rounded-xl border-gray-200 text-sm text-brand-navy placeholder:text-gray-400 focus:border-brand-teal focus:ring-brand-teal';
</script>

<template>
    <form
        class="flex min-h-0 flex-1"
        :class="props.layout === 'page' ? 'flex-col gap-8 lg:grid lg:grid-cols-[1fr_24rem] lg:items-start' : 'flex-col overflow-hidden'"
        @submit.prevent="submit"
    >
        <div
            :class="props.layout === 'page'
                ? 'space-y-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 sm:p-8'
                : 'flex-1 space-y-6 overflow-y-auto px-5 py-5'"
        >
            <div
                v-if="mustSignIn"
                class="overflow-hidden rounded-2xl bg-brand-navy p-5 text-white shadow-lg shadow-brand-navy/20"
            >
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0A17.9 17.9 0 0112 21.75c-2.7 0-5.2-.6-7.5-1.65z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="font-semibold">Sign in to place your order</p>
                        <p class="mt-0.5 text-sm text-white/70">This shop needs an account for orders. Your cart stays saved.</p>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <Link :href="signInUrl" class="rounded-xl bg-brand-orange py-2.5 text-center text-sm font-semibold text-white transition hover:bg-brand-orange-dark">
                        Sign in
                    </Link>
                    <Link :href="registerUrl" class="rounded-xl bg-white/10 py-2.5 text-center text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20">
                        Create account
                    </Link>
                </div>
            </div>
            <div
                v-else-if="shopCustomer"
                class="flex items-center gap-3 rounded-2xl bg-brand-teal/10 px-4 py-3 ring-1 ring-brand-teal/20"
            >
                <svg class="h-5 w-5 shrink-0 text-brand-teal-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="min-w-0 flex-1 truncate text-sm text-brand-navy">
                    Signed in as <span class="font-semibold">{{ shopCustomer.email }}</span>
                </p>
                <Link :href="route('shop.account.index')" class="shrink-0 text-xs font-semibold text-brand-teal-dark hover:underline">My orders</Link>
            </div>
            <p v-else class="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-600">
                Have an account?
                <Link :href="signInUrl" class="font-semibold text-brand-orange hover:underline">Sign in</Link>
                for faster checkout and order history.
            </p>

            <section>
                <h3 class="flex items-center gap-2 text-sm font-semibold text-brand-navy">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-navy text-xs text-white">1</span>
                    Contact
                </h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label class="block sm:col-span-2">
                        <span class="text-xs font-medium text-gray-600">Full name</span>
                        <input v-model="form.customer_name" type="text" autocomplete="name" required :class="inputClass" placeholder="Your name" />
                        <InputError class="mt-1" :message="form.errors.customer_name" />
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium text-gray-600">Phone number <span v-if="!phoneRequired" class="text-gray-400">(recommended)</span></span>
                        <input v-model="form.customer_phone" type="tel" inputmode="tel" autocomplete="tel" :required="phoneRequired" :class="inputClass" placeholder="01XXXXXXXXX" />
                        <InputError class="mt-1" :message="form.errors.customer_phone" />
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium text-gray-600">Email <span class="text-gray-400">(optional)</span></span>
                        <input v-model="form.customer_email" type="email" autocomplete="email" :class="inputClass" placeholder="you@example.com" />
                        <InputError class="mt-1" :message="form.errors.customer_email" />
                    </label>
                </div>
            </section>

            <section v-if="totals.delivery.value.enabled && totals.delivery.value.zones.length">
                <h3 class="flex items-center gap-2 text-sm font-semibold text-brand-navy">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-navy text-xs text-white">2</span>
                    Delivery area
                </h3>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="zone in totals.delivery.value.zones"
                        :key="zone.code"
                        class="relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border px-4 py-3 transition"
                        :class="form.delivery_zone === zone.code
                            ? 'border-brand-orange bg-brand-orange/5 ring-2 ring-brand-orange/20'
                            : 'border-gray-200 hover:border-gray-300'"
                    >
                        <input v-model="form.delivery_zone" type="radio" name="delivery_zone" :value="zone.code" class="sr-only" />
                        <span class="flex items-center gap-2.5">
                            <span
                                class="flex h-4 w-4 items-center justify-center rounded-full border-2"
                                :class="form.delivery_zone === zone.code ? 'border-brand-orange' : 'border-gray-300'"
                            >
                                <span v-if="form.delivery_zone === zone.code" class="h-2 w-2 rounded-full bg-brand-orange" />
                            </span>
                            <span class="text-sm font-medium text-brand-navy">{{ zone.name }}</span>
                        </span>
                        <span class="text-sm font-semibold" :class="totals.zoneFee(zone) === 0 ? 'text-emerald-600' : 'text-brand-navy'">
                            <template v-if="totals.zoneFee(zone) === 0">
                                <span v-if="Number(zone.rate) > 0" class="mr-1 text-xs font-normal text-gray-400 line-through">{{ money(zone.rate) }}</span>FREE
                            </template>
                            <template v-else>{{ money(zone.rate) }}</template>
                        </span>
                    </label>
                </div>
                <InputError class="mt-1" :message="form.errors.delivery_zone" />
            </section>

            <section>
                <h3 class="flex items-center gap-2 text-sm font-semibold text-brand-navy">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-navy text-xs text-white">
                        {{ totals.delivery.value.enabled && totals.delivery.value.zones.length ? 3 : 2 }}
                    </span>
                    Delivery address
                </h3>
                <div class="mt-3 grid gap-3">
                    <label class="block">
                        <span class="text-xs font-medium text-gray-600">District</span>
                        <select v-model="form.district" :class="inputClass">
                            <option v-for="district in districts" :key="district" :value="district">{{ district }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium text-gray-600">Full address</span>
                        <textarea
                            v-model="form.shipping_address"
                            rows="3"
                            required
                            :class="inputClass"
                            placeholder="House, road, area, landmark"
                        />
                        <InputError class="mt-1" :message="form.errors.shipping_address" />
                    </label>
                    <div>
                        <button v-if="!showNote" type="button" class="text-xs font-medium text-brand-teal-dark hover:underline" @click="showNote = true">
                            + Add a note for the delivery
                        </button>
                        <label v-else class="block">
                            <span class="text-xs font-medium text-gray-600">Delivery note <span class="text-gray-400">(optional)</span></span>
                            <input v-model="form.notes" type="text" :class="inputClass" placeholder="e.g. Call before coming" />
                        </label>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="flex items-center gap-2 text-sm font-semibold text-brand-navy">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-navy text-xs text-white">
                        {{ totals.delivery.value.enabled && totals.delivery.value.zones.length ? 4 : 3 }}
                    </span>
                    Payment
                </h3>
                <div class="mt-3 grid gap-2">
                    <label
                        v-for="option in paymentOptions"
                        :key="option.code"
                        class="relative flex items-center gap-3 rounded-xl border px-4 py-3 transition"
                        :class="[
                            form.payment_method === option.code
                                ? 'border-brand-orange bg-brand-orange/5 ring-2 ring-brand-orange/20'
                                : 'border-gray-200 hover:border-gray-300',
                            isOptionAvailable(option) ? 'cursor-pointer' : 'cursor-not-allowed opacity-60',
                        ]"
                    >
                        <input
                            v-model="form.payment_method"
                            type="radio"
                            name="payment_method"
                            :value="option.code"
                            :disabled="!isOptionAvailable(option)"
                            class="sr-only"
                        />
                        <span
                            class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2"
                            :class="form.payment_method === option.code ? 'border-brand-orange' : 'border-gray-300'"
                        >
                            <span v-if="form.payment_method === option.code" class="h-2 w-2 rounded-full bg-brand-orange" />
                        </span>
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg shadow-sm"
                            :class="option.code === 'online' ? 'bg-brand-navy text-white' : 'bg-white text-emerald-600 ring-1 ring-gray-100'"
                        >
                            <svg v-if="option.code === 'online'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-brand-navy">{{ option.label }}</span>
                            <span class="block text-xs text-gray-500">
                                {{ isOptionAvailable(option) ? option.description : `Available on orders from ${money(option.minimum)}` }}
                            </span>
                            <span v-if="option.code === 'online'" class="mt-1.5 flex flex-wrap gap-1">
                                <span
                                    v-for="brand in walletBrands"
                                    :key="brand"
                                    class="rounded bg-white px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 ring-1 ring-gray-200"
                                >
                                    {{ brand }}
                                </span>
                            </span>
                        </span>
                    </label>
                </div>
                <p v-if="!paymentOptions.length" class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    This shop isn't taking orders online right now. Please contact the shop directly.
                </p>
                <InputError class="mt-1" :message="form.errors.payment_method" />
            </section>
        </div>

        <div
            :class="props.layout === 'page'
                ? 'rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 lg:sticky lg:top-24'
                : 'border-t border-gray-100 bg-gray-50/70 px-5 py-4'"
        >
            <template v-if="props.layout === 'page'">
                <h3 class="text-sm font-semibold text-brand-navy">Order summary</h3>
                <ul class="mt-4 max-h-72 space-y-3 overflow-y-auto">
                    <li v-for="item in cart.items" :key="`${item.product_id}-${item.product_variant_id || 0}`" class="flex items-center gap-3">
                        <div class="relative h-12 w-12 shrink-0 overflow-hidden rounded-xl bg-gray-50">
                            <img v-if="item.image_url" :src="item.image_url" alt="" class="h-full w-full object-cover" />
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-navy px-1 text-[10px] font-semibold text-white">
                                {{ Number(item.quantity).toFixed(0) }}
                            </span>
                        </div>
                        <p class="min-w-0 flex-1 truncate text-sm text-brand-navy">{{ item.name }}</p>
                        <p class="shrink-0 text-sm font-medium text-brand-navy">{{ money(item.line_total) }}</p>
                    </li>
                </ul>
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <ShopCouponField />
                </div>
            </template>
            <div v-else class="mb-3">
                <ShopCouponField />
            </div>

            <dl class="space-y-1.5 text-sm" :class="props.layout === 'page' ? 'mt-4 border-t border-gray-100 pt-4' : ''">
                <div class="flex justify-between text-gray-600">
                    <dt>Subtotal</dt>
                    <dd>{{ money(totals.subtotal.value) }}</dd>
                </div>
                <div v-if="totals.discount.value > 0" class="flex justify-between text-emerald-600">
                    <dt>Coupon discount</dt>
                    <dd>−{{ money(totals.discount.value) }}</dd>
                </div>
                <div class="flex justify-between text-gray-600">
                    <dt>Delivery</dt>
                    <dd>
                        <span v-if="totals.shippingFee.value === null" class="text-gray-400">Choose area</span>
                        <span v-else-if="totals.shippingFee.value === 0" class="font-medium text-emerald-600">FREE</span>
                        <span v-else>{{ money(totals.shippingFee.value) }}</span>
                    </dd>
                </div>
                <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold text-brand-navy">
                    <dt>Total</dt>
                    <dd>{{ money(totals.total.value) }}</dd>
                </div>
            </dl>

            <p
                v-if="totals.freeRemaining.value !== null && totals.freeRemaining.value > 0"
                class="mt-2 text-xs text-gray-500"
            >
                Add {{ money(totals.freeRemaining.value) }} more for free delivery.
            </p>

            <p v-if="generalError" class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{{ generalError }}</p>

            <button
                type="submit"
                class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-brand-orange py-3.5 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="!canSubmit"
            >
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                </svg>
                <template v-if="form.processing">{{ paysOnline ? 'Taking you to payment…' : 'Placing order…' }}</template>
                <template v-else>{{ paysOnline ? `Pay ${money(totals.total.value)} securely` : `Place order · ${money(totals.total.value)}` }}</template>
            </button>
            <p class="mt-2 flex items-center justify-center gap-1.5 text-center text-[11px] text-gray-400">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                {{ paysOnline ? 'You will pay on the secure SSLCommerz page, then come back here.' : 'Your details are only used to deliver this order.' }}
            </p>
        </div>
    </form>
</template>
