<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    order: { type: Object, required: true },
    payment: { type: Object, default: () => ({ can_pay_online: false, can_pay_on_delivery: false, notice: null }) },
    ownsOrder: { type: Boolean, default: false },
});

const copied = ref(false);
const paymentForm = useForm({});

const isOnline = computed(() => props.order.payment_method === 'online');
const isPaid = computed(() => props.order.payment_status === 'paid');
const awaitingPayment = computed(() => isOnline.value && !isPaid.value && props.order.status === 'pending');

const payNow = () => paymentForm.post(props.payment.pay_url);
const payOnDelivery = () => paymentForm.post(props.payment.cash_on_delivery_url, { preserveScroll: true });

const notices = {
    success: {
        tone: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        title: 'Payment successful',
        body: 'Thank you! Your payment was received and the shop has been notified.',
    },
    failed: {
        tone: 'bg-red-50 text-red-800 ring-red-200',
        title: "Your payment didn't go through",
        body: 'Your order is saved. You can try again, or pay cash on delivery instead.',
    },
    cancelled: {
        tone: 'bg-gray-50 text-gray-700 ring-gray-200',
        title: 'Payment cancelled',
        body: "No problem — your order is saved. Pay whenever you're ready.",
    },
    unavailable: {
        tone: 'bg-amber-50 text-amber-800 ring-amber-200',
        title: "We couldn't open the payment page",
        body: 'The payment service did not respond. Your order is saved — please try again in a moment.',
    },
    unconfirmed: {
        tone: 'bg-amber-50 text-amber-800 ring-amber-200',
        title: 'Confirming your payment',
        body: "If money was deducted, this page will show it as paid shortly. Please don't pay twice — refresh in a minute.",
    },
};

const notice = computed(() => {
    const key = props.payment.notice;
    if (key === 'success' && !isPaid.value) {
        return notices.unconfirmed;
    }
    if (key && key !== 'success' && isPaid.value) {
        return notices.success;
    }
    return key ? notices[key] : null;
});

const money = (amount) => {
    const value = Number(amount || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return props.order.currency === 'BDT' ? `৳${value}` : `${props.order.currency} ${value}`;
};

const formatDate = (iso) =>
    iso
        ? new Date(iso).toLocaleString('en-BD', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' })
        : null;

const isCancelled = computed(() => props.order.status === 'cancelled');

const statusBadge = computed(() => ({
    pending: 'bg-amber-50 text-amber-700 ring-amber-200',
    confirmed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    cancelled: 'bg-red-50 text-red-700 ring-red-200',
})[props.order.status] ?? 'bg-gray-50 text-gray-700 ring-gray-200');

const headline = computed(() => {
    if (awaitingPayment.value) {
        return 'Complete your payment';
    }
    if (isPaid.value && props.order.status === 'pending') {
        return 'Payment received — thank you!';
    }
    return ({
        pending: 'We received your order',
        confirmed: 'Your order is confirmed',
        cancelled: 'This order was cancelled',
    })[props.order.status] ?? 'Order status';
});

const subline = computed(() => {
    if (awaitingPayment.value) {
        return 'Your order is saved. It goes to the shop as soon as your payment is done.';
    }
    if (isPaid.value && props.order.status === 'pending') {
        return 'The shop has your order and will confirm it shortly.';
    }
    return ({
        pending: 'The shop will call you shortly to confirm. Keep your phone nearby.',
        confirmed: 'Your items are being packed and will be on their way soon.',
        cancelled: 'If this is unexpected, please contact the shop.',
    })[props.order.status] ?? '';
});

const steps = computed(() => {
    const placed = { key: 'placed', label: 'Order placed', at: props.order.created_at, done: true };

    if (isCancelled.value) {
        return [placed, { key: 'cancelled', label: 'Cancelled', at: props.order.cancelled_at, done: true, danger: true }];
    }

    return [
        placed,
        ...(isOnline.value ? [{ key: 'paid', label: 'Paid', at: props.order.paid_at, done: isPaid.value }] : []),
        { key: 'confirmed', label: 'Confirmed', at: props.order.confirmed_at, done: Boolean(props.order.confirmed_at) },
        { key: 'delivery', label: 'Delivery', at: null, done: false },
    ];
});

const stepColumns = computed(() => ({ 2: 'sm:grid-cols-2', 3: 'sm:grid-cols-3', 4: 'sm:grid-cols-4' })[steps.value.length]);

const paymentBadge = computed(() => ({
    paid: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    pending: 'bg-amber-50 text-amber-700 ring-amber-200',
    failed: 'bg-red-50 text-red-700 ring-red-200',
})[props.order.payment_status] ?? 'bg-gray-50 text-gray-600 ring-gray-200');

const shippingLines = computed(() => (props.order.shipping_address || '').split('\n').filter(Boolean));

const copyLink = async () => {
    if (!props.order.tracking_url) {
        return;
    }
    try {
        await navigator.clipboard.writeText(props.order.tracking_url);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        copied.value = false;
    }
};
</script>

<template>
    <Head :title="`Order ${order.number}`" />

    <StorefrontLayout>
        <div class="w-full bg-gradient-to-b from-brand-orange/5 via-white to-white">
            <div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
                <div v-if="notice" class="mb-6 flex items-start gap-3 rounded-2xl px-5 py-4 ring-1" :class="notice.tone" role="status">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold">{{ notice.title }}</p>
                        <p class="mt-0.5 text-sm opacity-90">{{ notice.body }}</p>
                    </div>
                </div>

                <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-black/5">
                    <div class="flex flex-col gap-5 px-6 py-7 sm:flex-row sm:items-start sm:justify-between sm:px-8">
                        <div class="flex items-start gap-4">
                            <span
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-white shadow-lg"
                                :class="isCancelled
                                    ? 'bg-red-500 shadow-red-500/25'
                                    : awaitingPayment ? 'bg-brand-orange shadow-brand-orange/25' : 'bg-emerald-500 shadow-emerald-500/25'"
                            >
                                <svg v-if="isCancelled" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <svg v-else-if="awaitingPayment" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                                <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-orange">Order {{ order.number }}</p>
                                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-navy sm:text-3xl">{{ headline }}</h1>
                                <p class="mt-2 max-w-xl text-sm text-gray-600">{{ subline }}</p>
                            </div>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-full px-3 py-1 text-xs font-semibold ring-1" :class="statusBadge">
                            <span class="h-1.5 w-1.5 rounded-full bg-current" />
                            {{ order.status_label }}
                        </span>
                    </div>

                    <div
                        v-if="payment.can_pay_online || payment.can_pay_on_delivery"
                        class="flex flex-col gap-4 border-t border-brand-orange/20 bg-gradient-to-r from-brand-orange/10 via-brand-orange/5 to-transparent px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8"
                    >
                        <div>
                            <p class="text-sm font-semibold text-brand-navy">Amount due: {{ money(order.grand_total) }}</p>
                            <p class="mt-0.5 text-xs text-gray-600">
                                {{ payment.can_pay_online ? 'Pay with bKash, Nagad, Rocket or card on the secure SSLCommerz page.' : 'Online payment is unavailable right now.' }}
                            </p>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <button
                                v-if="payment.can_pay_on_delivery"
                                type="button"
                                class="rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-navy ring-1 ring-gray-200 transition hover:bg-white disabled:opacity-60"
                                :disabled="paymentForm.processing"
                                @click="payOnDelivery"
                            >
                                Pay cash on delivery instead
                            </button>
                            <button
                                v-if="payment.can_pay_online"
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-orange px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark disabled:opacity-60"
                                :disabled="paymentForm.processing"
                                @click="payNow"
                            >
                                <svg v-if="paymentForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                                </svg>
                                {{ order.payment_status === 'failed' ? 'Try again' : 'Pay' }} {{ money(order.grand_total) }}
                            </button>
                        </div>
                    </div>

                    <ol class="grid gap-4 border-t border-gray-100 bg-gray-50/60 px-6 py-6 sm:px-8" :class="stepColumns">
                        <li v-for="(step, index) in steps" :key="step.key" class="relative flex items-center gap-3 sm:flex-col sm:items-start">
                            <div class="flex items-center gap-3 sm:w-full">
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ring-4 ring-white"
                                    :class="step.danger ? 'bg-red-500 text-white' : step.done ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-500'"
                                >
                                    <svg v-if="step.done && !step.danger" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span v-else-if="!step.done">{{ index + 1 }}</span>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </span>
                                <span
                                    v-if="index < steps.length - 1"
                                    class="hidden h-0.5 flex-1 rounded-full sm:block"
                                    :class="steps[index + 1].done ? (steps[index + 1].danger ? 'bg-red-300' : 'bg-emerald-300') : 'bg-gray-200'"
                                />
                            </div>
                            <div>
                                <p class="text-sm font-semibold" :class="step.done ? 'text-brand-navy' : 'text-gray-400'">{{ step.label }}</p>
                                <p class="text-xs text-gray-500">{{ formatDate(step.at) ?? (step.done ? '' : 'Waiting') }}</p>
                            </div>
                        </li>
                    </ol>
                </section>

                <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_20rem]">
                    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 sm:p-8">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Items</h2>
                        <ul class="mt-4 divide-y divide-gray-100">
                            <li v-for="item in order.items" :key="item.id" class="flex items-start justify-between gap-4 py-3">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-orange/10 text-sm font-semibold text-brand-orange">
                                        {{ Number(item.quantity) }}×
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-brand-navy">{{ item.name }}</p>
                                        <p class="text-xs text-gray-500">{{ money(item.unit_price) }} each</p>
                                    </div>
                                </div>
                                <p class="shrink-0 font-semibold text-brand-navy">{{ money(item.line_total) }}</p>
                            </li>
                        </ul>

                        <dl class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <dt>Subtotal</dt>
                                <dd>{{ money(order.subtotal ?? order.grand_total) }}</dd>
                            </div>
                            <div v-if="Number(order.discount_total) > 0" class="flex justify-between text-emerald-600">
                                <dt>Coupon <span class="font-medium">{{ order.coupon_code }}</span></dt>
                                <dd>−{{ money(order.discount_total) }}</dd>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <dt>Delivery<span v-if="order.delivery_zone" class="text-gray-400"> · {{ order.delivery_zone }}</span></dt>
                                <dd :class="Number(order.shipping_fee) === 0 ? 'font-medium text-emerald-600' : ''">
                                    {{ Number(order.shipping_fee) === 0 ? 'FREE' : money(order.shipping_fee) }}
                                </dd>
                            </div>
                            <div class="flex justify-between pt-2 text-base font-semibold text-brand-navy">
                                <dt>Total</dt>
                                <dd>{{ money(order.grand_total) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <aside class="space-y-6">
                        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Delivery to</h2>
                            <p class="mt-3 font-medium text-brand-navy">{{ order.customer_name }}</p>
                            <p v-if="order.customer_phone" class="text-sm text-gray-600">{{ order.customer_phone }}</p>
                            <p v-for="(line, i) in shippingLines" :key="i" class="text-sm text-gray-500">{{ line }}</p>
                        </section>

                        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Payment</h2>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1" :class="paymentBadge">
                                    {{ order.payment_status_label }}
                                </span>
                            </div>
                            <p class="mt-3 font-medium text-brand-navy">
                                {{ order.payment_method_label }}<span v-if="order.payment_card_type" class="text-gray-500"> · {{ order.payment_card_type }}</span>
                            </p>
                            <p v-if="isPaid" class="text-sm text-gray-500">Paid {{ formatDate(order.paid_at) }}</p>
                            <p v-else class="text-sm text-gray-500">Placed {{ formatDate(order.created_at) }}</p>
                            <p v-if="isPaid && order.payment_transaction_id" class="mt-2 break-all text-xs text-gray-400">
                                Ref: {{ order.payment_transaction_id }}
                            </p>
                        </section>

                        <section class="rounded-3xl bg-brand-navy p-6 text-white shadow-sm">
                            <h2 class="text-sm font-semibold">Save this page</h2>
                            <p class="mt-1 text-sm text-white/70">Use this private link any time to check your order status.</p>
                            <button
                                type="button"
                                class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold ring-1 ring-white/20 transition hover:bg-white/20"
                                @click="copyLink"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                {{ copied ? 'Link copied' : 'Copy order link' }}
                            </button>
                        </section>
                    </aside>
                </div>

                <div class="mt-8 flex flex-col-reverse items-center justify-center gap-3 sm:flex-row">
                    <Link
                        v-if="ownsOrder"
                        :href="route('shop.account.index')"
                        class="inline-flex items-center gap-2 rounded-xl px-6 py-3 text-sm font-semibold text-brand-navy ring-1 ring-gray-200 transition hover:bg-gray-50"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        My orders
                    </Link>
                    <Link
                        :href="route('shop.index')"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-orange px-6 py-3 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark"
                    >
                        Continue shopping
                    </Link>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
